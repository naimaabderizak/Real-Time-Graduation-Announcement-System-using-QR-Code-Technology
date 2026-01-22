# Smart Graduation System (SGS) - Setup Instructions

## 1. Prerequisites
- **XAMPP/WAMP/MAMP** (For PHP & MySQL)
- **Python 3.9+** (For Real-time Server)

## 2. Database Setup
1. Open **phpMyAdmin**.
2. Create a new database named `sgs_db`.
3. Import the `db_schema.sql` file provided in the root directory.

## 3. PHP Admin Panel Setup
1. Move the project folder to `htdocs/`.
2. Ensure the `assets/uploads/` directory is writable.
3. Access the admin at: `http://localhost/Smart Graduation System (SGS)/admin/login.php`
   - **Username:** `admin`
   - **Password:** `admin123`

## 4. Real-time Backend (Python) Setup
1. Open a terminal in the `realtime/` directory.
2. Install dependencies (Try `python -m pip` if `pip` doesn't work):
   ```bash
   python -m pip install fastapi uvicorn python-socketio mysql-connector-python
   ```
3. Run the server:
   ```bash
   python server.py
   ```
   *Note: If `python` is not recognized, try `python3`.*

## 5. Usage
1. **Grand Screen:** Open `display/index.php` on the theater screen.
2. **Scanner:** Open `scanner/index.php` on the scanning device (laptop with webcam or USB scanner).
3. **Scan:** When a QR is scanned, the Grand Screen will update automatically.

## 6. LAN / Offline Usage
To use across a local network:
1. Find your Server IP (e.g., `192.168.1.10`).
2. Update the `pythonServerUrl` in `scanner/index.php` and the `io()` endpoint in `display/index.php` to use this IP instead of `localhost`.
