# Oryxpace Setup & Hosting Guide

---

## 1. Clone the Project

1. Create a folder on your server or computer where you want to keep the project.
2. Open a terminal and run:

```bash
git clone https://github.com/Bumyy/Oryxpace.git
```

3. Navigate into the folder:

```bash
cd Oryxpace
```

---

## 2. Environment Variable Setup (.env)

- In the project's root folder, **copy** the `.env.example` file and **paste** it in the same folder.
- Rename the copied file to `.env` (remove `.example`).
- Open the `.env` file and enter your MySQL database connection details and Vite API base URL:

```env
# Backend (MySQL Database Connection of Oryxie)
DB_HOST=YOUR_MYSQL_HOST
DB_USER=YOUR_MYSQL_USERNAME
DB_PASSWORD=YOUR_MYSQL_PASSWORD
DB_NAME=YOUR_MYSQL_DATABASE
DB_PORT=3306

# Frontend (Vite Base URL)
# Keep as http://127.0.0.1:8000 for local testing.
# Update to your live Django API domain in production (e.g., https://api.qatarivirtual.xyz)
VITE_API_BASE_URL=http://127.0.0.1:8000
```

---

## 3. Backend Setup & Deployment (Django API)

The backend is built with Django REST Framework and connects directly to the **Oryxie** database.

### Local Run:
1. Move into the backend directory:
   ```bash
   cd backend
   ```
2. Install python dependencies:
   ```bash
   pip install -r ../requirements.txt
   ```
3. Run the development server:
   ```bash
   python manage.py runserver
   ```
   *(Backend will run at `http://127.0.0.1:8000/`)*

### Production Deployment:
- Deploy the `/backend` folder to a VPS, **Render**, **Railway**, or **Koyeb**.
- Configure the environment variables (`DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_PORT`) in your hosting provider's dashboard.
- Set up CORS if hosting the backend and frontend on different domains (Django is configured to accept custom requests).

---

## 4. Frontend Setup & Deployment (React)

The frontend is a lightweight, responsive React Single Page Application styled with Tailwind CSS v4 and DaisyUI.

### Local Run:
1. Move into the frontend directory:
   ```bash
   cd frontend
   ```
2. Install npm packages:
   ```bash
   npm install
   ```
3. Start the development server:
   ```bash
   npm run dev
   ```
   *(Frontend will run at `http://localhost:5173/`)*

### Production Deployment:
1. Move into the frontend directory:
   ```bash
   cd frontend
   ```
2. Build the static assets:
   ```bash
   npm run build
   ```
3. Deploy the compiled `dist/` folder to any static web host such as **Vercel**, **Netlify**, or **GitHub Pages**.
4. Remember to set the environment variable `VITE_API_BASE_URL` to your production backend API URL in your static host's build settings.

---

## Summary

- **Root .env**: Configure MySQL database and API URLs.
- **Backend (Django)**: Runs on port `8000`. Connects to Oryxie DB.
- **Frontend (React)**: Runs on port `5173`. Commits static build to `dist/`.

---

If you have any questions or need server deployment assistance, feel free to ask! 🚀
