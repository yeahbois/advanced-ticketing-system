# Open House Ticketing System

A comprehensive ticketing system designed for the "Open House" event at SMAN Unggulan M.H. Thamrin. This project consists of three main components: a Laravel backend API, a Vite React frontend scanner, and Python scripts for ticket generation and bulk mailing.

## Project Structure

- `ticket-api-laravel/`: The backend API and database management.
- `ticket-scanner-vitereact/`: The web-based QR code scanner for attendee check-in.
- `ticket-generation-and-bulkmailer-python/`: Python scripts for generating e-tickets and sending them via email.

---

## 1. Backend for Ticketing System (Laravel)

The backend serves as the central hub for data management, providing APIs for the scanner and the Python scripts. It synchronizes data from Google Sheets to a local MySQL database for faster and more reliable access.

### Features
- **Data Synchronization**: Syncs attendee data from Google Sheets to a local MySQL database.
- **Ticket Validation**: Provides endpoints to check ticket validity and details.
- **Attendance Tracking**: Records attendance for Students (ANAK), Parent 1 (OTM1), and Parent 2 (OTM2).
- **Status Updates**: Tracks whether tickets have been sent or if delivery failed.
- **Role-based Logic**: Automatically validates roles (ANAK, OTM1, OTM2) based on the ticket type.

### Tech Stack
- **Framework**: Laravel 10/11
- **Database**: MySQL
- **Integration**: Google Sheets API

### Key API Endpoints
- `GET /api/oh/sync-sql`: Syncs data from Google Sheets to the local database.
- `GET /api/oh/get-all-data-sql`: Fetches all attendee data from the database.
- `GET /api/oh/present?qrString={ROLE_TICKETID}`: Records attendance for a specific role and ticket ID.
- `GET /api/oh/update-ticket-status`: Updates the delivery status of a ticket.

### Installation
1. Navigate to `ticket-api-laravel/`.
2. Install dependencies: `composer install`.
3. Copy `.env.example` to `.env` and configure your database and Google Sheets credentials.
4. Run migrations: `php artisan migrate`.
5. Place your Google Service Account credentials in `storage/credentials.json`.
6. Start the server: `php artisan serve`.

---

## 2. Frontend for Ticketing Scanner (Vite React)

A fast and responsive web application used by event staff to scan attendee QR codes. It interfaces directly with the Laravel API to record attendance in real-time.

### Features
- **QR Code Scanning**: Uses the device's camera to scan QR codes via the `html5-qrcode` library.
- **Real-time Feedback**: Provides instant visual alerts and audio cues for successful or failed check-ins.
- **Camera Selection**: Allows users to choose between multiple cameras (e.g., front vs. back).
- **Mirror Fix**: Automatically adjusts the video feed for a better scanning experience.

### Tech Stack
- **Framework**: React (Vite)
- **Styling**: Tailwind CSS
- **Library**: `html5-qrcode`

### Installation
1. Navigate to `ticket-scanner-vitereact/`.
2. Install dependencies: `npm install`.
3. Configure the API base URL in `src/pages/Home.tsx` if necessary.
4. Run the development server: `npm run dev`.
5. Build for production: `npm run build`.

---

## 3. Script for Bulk Mailing and Ticket Generation (Python)

Automation scripts to handle the heavy lifting of generating personalized e-tickets and distributing them to hundreds of attendees.

### Features
- **Ticket Generation (`generate_tickets.py`)**:
    - Generates unique QR codes for each role (Student and Parents) with the format `ROLE_TICKETID`.
    - Overlays attendee information (Name, Date, Room) onto a beautiful ticket template using PIL (Pillow).
- **Bulk Mailer (`bulkmailer.py`)**:
    - Sends e-tickets as attachments via SMTP.
    - **Multi-Mailbox Support**: Rotates through multiple email accounts to bypass daily sending limits.
    - **Status Reporting**: Updates the backend API with the delivery status of each ticket.

### Tech Stack
- **Language**: Python 3
- **Libraries**: `Pillow`, `qrcode`, `requests`, `python-dotenv`

### Installation
1. Navigate to `ticket-generation-and-bulkmailer-python/`.
2. Install dependencies: `pip install -r requirements.txt`.
3. Configure your environment variables in a `.env` file (see `bulkmailer.py` for required variables like `EMAIL1`, `PASSWD1`, etc.).
4. Run ticket generation: `python python-image/generate_tickets.py`.
5. Run bulk mailing: `python python-bulkmailer/bulkmailer.py`.

---

## Workflow Summary
1. **Sync**: Backend pulls latest registrant data from Google Sheets.
2. **Generate**: Python script fetches data from the Backend and generates ticket images.
3. **Mail**: Python script sends the generated tickets to attendees' emails.
4. **Scan**: On event day, staff use the React Scanner to check in attendees.
5. **Verify**: Backend validates the scan and records attendance in the database.
