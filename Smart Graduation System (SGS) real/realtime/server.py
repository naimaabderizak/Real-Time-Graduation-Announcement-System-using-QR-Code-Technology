import asyncio
import socketio
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
import mysql.connector
from datetime import datetime
import json
import sys

# Force UTF-8 for Windows Console
sys.stdout.reconfigure(encoding='utf-8')
import collections

# Database Configuration
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'sgs_db'
}

# Socket.io Server Setup
sio = socketio.AsyncServer(async_mode='asgi', cors_allowed_origins='*')
app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Queue for Scanned Students
scan_queue = collections.deque()
is_processing = False

# Award Reveal State Tracking
# States: idle, header_shown, rank1_shown, rank2_shown, all_done
award_session = {
    "state": "idle",
    "faculty": None,
    "last_student_id": None,
    "shown_ranks": []
}

def get_db_connection():
    return mysql.connector.connect(**db_config)

async def process_queue():
    global is_processing
    if is_processing or not scan_queue:
        return
    
    is_processing = True
    print(f">>> STARTING QUEUE PROCESSOR ({len(scan_queue)} items)")
    
    global award_session
    try:
        while scan_queue:
            student_data = scan_queue.popleft()
            
            # Convert datetime objects to strings 
            for key, value in student_data.items():
                if isinstance(value, datetime):
                    student_data[key] = value.isoformat()
            
            rank = student_data.get('student_rank')
            is_top_student = rank in [1, 2, 3]
            is_first_scan = student_data.get('_is_first_scan', False)
            
            # --- BROADCASTING LOGIC ---
            # Entry scans (1st time today) go to regular screens. 
            # Subsquent scans (stage) are handled by award reveal logic.
            if is_first_scan:
                print(f">>> ENTRY SCAN: {student_data['full_name']} (ID: {student_data['student_id']})")
                await sio.emit('new_scan', student_data)
            else:
                print(f">>> STAGE RE-SCAN: {student_data['full_name']} (ID: {student_data['student_id']})")

            # --- Strict Multi-Scan Award Logic ---
            if is_top_student:
                current_state = award_session["state"]
                current_fac = award_session["faculty"]
                student_id = student_data["student_id"]

                # A. RESET / START / RANK 1 Logic: Combined
                # Rank 1 ALWAYs triggers a sequence start/restart, regardless of current state.
                if rank == 1:
                    print(f">>> AWARD START (RESET): Faculty Header + Rank 1 ({student_data['full_name']})")
                    
                    # 1. Update Session Context
                    award_session["state"] = "rank1_shown" # We skip straight to rank1_shown
                    award_session["faculty"] = student_data["faculty"]
                    award_session["last_student_id"] = student_id
                    award_session["shown_ranks"] = [1]
                    
                    # 2. Emit Faculty Header
                    conn = get_db_connection()
                    cursor = conn.cursor(dictionary=True)
                    cursor.execute("SELECT * FROM faculties WHERE faculty_name = %s", (student_data["faculty"],))
                    faculty = cursor.fetchone()
                    conn.close()
                    
                    # Convert datetime objects in faculty record too
                    if faculty:
                        for k, v in faculty.items():
                            if isinstance(v, datetime):
                                faculty[k] = v.isoformat()
                    
                    await sio.emit('faculty_reveal', faculty if faculty else {"faculty_name": student_data["faculty"], "batch_name": ""})
                    
                    # 3. BRIEF DELAY for dramatic effect (Reader can read "Faculty of ...")
                    await asyncio.sleep(1.5)
                    
                    # 4. Emit Rank 1 Student Reveal
                    print(f">>> AWARD REVEAL: Rank 1 - {student_data['full_name']}")
                    await sio.emit('award_reveal', student_data)

                # B. REVEAL Sequence Logic
                elif student_data["faculty"] != current_fac:
                    print(f">>> REJECTED AWARDS: Wrong Faculty. Scanned {student_data['faculty']} but expected {current_fac}.")
                
                # Scan #3: Rank 2 (Allow anytime if not already shown)
                elif rank == 2 and 2 not in award_session["shown_ranks"]:
                    print(f">>> AWARD REVEAL: Rank 2 - {student_data['full_name']}")
                    await sio.emit('award_reveal', student_data)
                    award_session["state"] = "rank2_shown"
                    award_session["shown_ranks"].append(2)

                # Scan #4: Rank 3
                elif rank == 3 and current_state == "rank2_shown" and 3 not in award_session["shown_ranks"]:
                    print(f">>> AWARD REVEAL: Rank 3 - {student_data['full_name']}")
                    await sio.emit('award_reveal', student_data)
                    award_session["state"] = "all_done"
                    award_session["shown_ranks"].append(3)
                
                else:
                    print(f">>> AWARD IGNORED: Wrong sequence or duplicate scan. Rank {rank} at state {current_state}.")
            
            # Minimized sleep for fast queue processing
            await asyncio.sleep(0.3) 
                
    except Exception as e:
        print(f">>> QUEUE ERROR: {e}")
    finally:
        is_processing = False
        print(">>> QUEUE PROCESSOR STOPPED.")

@app.get("/test-broadcast")
async def test_broadcast():
    test_student = {
        "full_name": "Test Student",
        "phonetic_name": "Test Stoo-dent",
        "student_id": "TEST123",
        "faculty": "Computer Science",
        "photo_path": "assets/uploads/default.png"
    }
    print(">>> SENDING TEST BROADCAST")
    await sio.emit('new_scan', test_student)
    return {"status": "success", "message": "Test event emitted."}

@sio.event
async def connect(sid, environ):
    print(f"Client connected: {sid}")

@sio.event
async def disconnect(sid):
    print(f"Client disconnected: {sid}")

@app.get("/")
async def root():
    return {"status": "SGS Real-time Server is running"}

@app.post("/reset-awards")
async def reset_awards():
    global award_session
    award_session = {"state": "idle", "faculty": None, "last_activity": 0}
    await sio.emit('reset_screen', {})
    return {"status": "success", "message": "Award session reset."}

@app.post("/reveal-faculty/{faculty_id}")
async def reveal_faculty(faculty_id: int):
    # Keep as manual backup if needed
    print(f"Faculty Reveal triggered for ID: {faculty_id}")
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM faculties WHERE id = %s", (faculty_id,))
        faculty = cursor.fetchone()
        
        if faculty:
            await sio.emit('faculty_reveal', faculty)
            return {"status": "success", "message": f"Faculty {faculty['faculty_name']} revealed."}
        return {"status": "error", "message": "Faculty not found."}
    except Exception as e:
        return {"status": "error", "message": str(e)}
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# Track last scan times for cooldown logic
last_scan_times = {}

@app.post("/scan/{student_id}")
async def handle_scan(student_id: str):
    print(f"Received scan for ID: {student_id}")
    
    # Fetch student info first to check rank
    try:
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM students WHERE student_id = %s", (student_id,))
        student = cursor.fetchone()
        
        if not student:
            return {"status": "error", "message": "Student not found."}

        # Cooldown Logic
        # Exempt Top 3 students from the 2-minute cooldown to allow the "Scan twice" sequence
        is_top = student.get('student_rank') in [1, 2, 3]
        cooldown_duration = 3 if is_top else 5 # REDUCED TO 5 SECONDS for stage usage (was 120)
        
        current_ts = datetime.now().timestamp()
        if student_id in last_scan_times:
            elapsed = current_ts - last_scan_times[student_id]
            if elapsed < cooldown_duration:
                remaining = int(cooldown_duration - elapsed)
                print(f">>> COOLDOWN ACTIVE: {student_id} ignored. (Wait {remaining}s)")
                return {
                    "status": "warning",
                    "message": f"Please wait {remaining}s before scanning again."
                }

        # Determine if this is the FIRST scan of the day (Entry)
        is_first_scan = student.get('is_scanned') == 0
        student['_is_first_scan'] = is_first_scan

        # Update last scan time
        last_scan_times[student_id] = current_ts
        
        # Update scanned status
        now = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        cursor.execute("UPDATE students SET is_scanned = 1, scanned_at = %s WHERE student_id = %s", (now, student_id))
        conn.commit()
        
        # Add to queue
        scan_queue.append(student)
        
        # Trigger queue processing
        asyncio.create_task(process_queue())
        
        return {"status": "success", "message": f"Student {student['full_name']} queued."}

    except Exception as e:
        print(f"Error in handle_scan: {e}")
        return {"status": "error", "message": str(e)}
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()

# Combine Socket.io and FastAPI AFTER all routes are defined
sio_app = socketio.ASGIApp(sio, app)

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(sio_app, host="0.0.0.0", port=5001)
