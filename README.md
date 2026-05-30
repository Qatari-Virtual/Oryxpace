# Oryxpace, Virtual Airline Operations Setup Guide

---

## 1. Project Overview

Oryxpace is a premium, full-stack virtual airline operations hub featuring:
- **Frontend**: Vite + React SPA (Tailwind CSS v4 + DaisyUI)
- **Backend API**: Django REST Framework (Python)
- **Database**: Integrated directly with your existing MySQL Database used by Oryxie.

---

## 2. Environment Variables Setup (.env)

Create a `.env` file at the root of the project. This file is ignored by git for security. Add the following variables:

```env
# Backend (MySQL Database Connection)
DB_HOST=your-mysql-host
DB_USER=your-mysql-username
DB_PASSWORD=your-mysql-password
DB_NAME=your-mysql-database
DB_PORT=3306

# Frontend (Vite Base URL)
# Set this to your live Django API URL in production (e.g. https://api.qatarivirtual.xyz)
VITE_API_BASE_URL=http://127.0.0.1:8000
```

---

## 3. Backend Setup & Run (Django)

Make sure you have **Python 3.10+** installed.

1. Move into the backend directory:
   ```bash
   cd backend
   ```
2. Create and activate a Python virtual environment:
   ```bash
   python -m venv .venv
   # Windows:
   ..\.venv\Scripts\activate
   # Linux/macOS:
   source ../.venv/bin/activate
   ```
3. Install dependencies:
   ```bash
   pip install -r ../requirements.txt
   ```
4. Start the Django API server:
   ```bash
   python manage.py runserver
   ```
   *(Server starts at `http://127.0.0.1:8000/`)*

---

## 4. Frontend Setup & Run (React)

Make sure you have **Node.js 18+** installed.

1. Move into the frontend directory:
   ```bash
   cd frontend
   ```
2. Install package dependencies:
   ```bash
   npm install
   ```
3. Start the Vite React development server:
   ```bash
   npm run dev
   ```
   *(Server starts at `http://localhost:5173/`)*

---

## 5. Production Deployment

- **Frontend**: The React app is static-build friendly. Run `npm run build` inside `frontend/` and deploy the `dist/` folder to **Vercel**, **Netlify**, or any static web host. Make sure to set `VITE_API_BASE_URL` in your build environment.
- **Backend**: Deploy the `backend/` directory to **Render**, **Koyeb**, or any VPS. Configure your environment variables in your server hosting dashboard.
