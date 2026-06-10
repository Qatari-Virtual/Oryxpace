import React, { useState, useEffect } from 'react'
import { BrowserRouter, Routes, Route, Link, useLocation, Navigate, useNavigate } from 'react-router-dom'
import { useSelector, useDispatch } from 'react-redux'
import axios from 'axios'
import { toggleSidebar, setCredentials, logout } from './store/slices/appSlice'
import { 
  LayoutDashboard, 
  Menu, 
  Database,
  PlaneTakeoff,
  ClipboardCheck,
  LogOut,
  Loader2,
  Calendar
} from 'lucide-react'

// Import Pages
import Dashboard from './pages/Dashboard'
import EvaluatorForm from './pages/EvaluatorForm'
import Login from './pages/Login'
import Missions from './pages/Missions'

import { API_BASE_URL } from './config'

function AuthInitializer({ children }) {
  const dispatch = useDispatch()
  const token = useSelector((state) => state.app.token)
  const user = useSelector((state) => state.app.user)
  const [initializing, setInitializing] = useState(true)

  useEffect(() => {
    const fetchMe = async () => {
      if (!token) {
        // No token, clear axios headers and finish initializing
        delete axios.defaults.headers.common['Authorization']
        setInitializing(false)
        return
      }

      // Configure axios global header
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

      try {
        const response = await fetch(`${API_BASE_URL}/api/auth/me/`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        })

        if (!response.ok) {
          throw new Error('Session expired')
        }

        const data = await response.json()
        dispatch(setCredentials({ user: data, token }))
      } catch (err) {
        console.error('Session validation failed:', err)
        dispatch(logout())
        delete axios.defaults.headers.common['Authorization']
      } finally {
        setInitializing(false)
      }
    }

    fetchMe()
  }, [token, dispatch])

  if (initializing) {
    return (
      <div className="h-screen w-screen flex flex-col items-center justify-center bg-slate-950 text-white font-sans">
        <Loader2 className="w-10 h-10 animate-spin text-primary mb-4" />
        <p className="text-sm text-slate-400">Loading Oryxpace...</p>
      </div>
    )
  }

  return children
}

function ProtectedRoute({ children }) {
  const { user, token } = useSelector((state) => state.app)

  if (!token) {
    return <Navigate to="/login" replace />
  }

  if (!user) {
    return (
      <div className="h-screen w-screen flex flex-col items-center justify-center bg-slate-950 text-white font-sans">
        <Loader2 className="w-10 h-10 animate-spin text-primary mb-4" />
        <p className="text-sm text-slate-400">Loading profile...</p>
      </div>
    )
  }

  return children
}

function MainLayout() {
  const dispatch = useDispatch()
  const location = useLocation()
  const navigate = useNavigate()
  const { user, sidebarOpen } = useSelector((state) => state.app)

  const userCallsignNum = user?.callsign ? user.callsign.replace(/\D/g, '') : ''
  const isMissionManager = ["001", "002", "003", "004"].includes(userCallsignNum)

  const menuItems = [
    { name: 'Flight Evaluator', path: '/', icon: ClipboardCheck },
    { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
  ]

  if (isMissionManager) {
    menuItems.push({ name: 'Missions Dispatcher', path: '/missions', icon: Calendar })
  }

  const handleLogout = () => {
    dispatch(logout())
    delete axios.defaults.headers.common['Authorization']
    navigate('/login')
  }

  return (
    <div className="flex h-screen overflow-hidden bg-brand-bg">
      {/* Sidebar */}
      <aside className={`bg-brand-card border-r border-brand-border transition-all duration-300 flex flex-col justify-between z-20 ${sidebarOpen ? 'w-64' : 'w-20'}`}>
        <div>
          <div className="flex items-center gap-3 px-5 py-5 border-b border-brand-border h-[73px]">
            {sidebarOpen ? (
              <img 
                src={`${API_BASE_URL}/static/Qatar_Virtual_logo.PNG`} 
                alt="Qatar Virtual Logo" 
                className="h-10 w-auto object-contain mx-auto transition-all duration-300"
              />
            ) : (
              <div className="h-9 w-9 rounded-xl overflow-hidden bg-brand-bg border border-brand-border flex-shrink-0 flex items-center justify-center mx-auto">
                <img 
                  src={`${API_BASE_URL}/static/Qatar_Virtual_logo.PNG`} 
                  alt="Qatar Virtual Logo" 
                  className="h-full w-full object-cover object-right scale-110"
                />
              </div>
            )}
          </div>

          {/* Navigation Links */}
          <nav className="p-4 space-y-2">
            {menuItems.map(item => {
              const Icon = item.icon
              const isActive = location.pathname === item.path
              return (
                <Link 
                  key={item.name}
                  to={item.path}
                  className={`flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 ${isActive ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-slate-600 hover:bg-brand-border/40 hover:text-brand-text'}`}
                >
                  <Icon className="w-5 h-5 flex-shrink-0" />
                  {sidebarOpen && <span className="font-medium text-sm">{item.name}</span>}
                </Link>
              )
            })}
          </nav>
        </div>

        {/* User Info & Logout Button */}
        <div className="p-4 border-t border-brand-border flex flex-col gap-3 bg-brand-card/50">
          <div className="flex items-center gap-3">
            <div className="avatar">
              <div className="w-10 h-10 rounded-xl border border-brand-border bg-brand-bg overflow-hidden flex items-center justify-center">
                <img 
                  src={user?.avatar || `https://api.dicebear.com/7.x/bottts/svg?seed=${user?.name}`} 
                  alt="avatar" 
                  className="w-full h-full object-cover" 
                  onError={(e) => {
                    e.target.onerror = null;
                    e.target.src = `https://api.dicebear.com/7.x/bottts/svg?seed=${user?.name}`;
                  }}
                />
              </div>
            </div>
            {sidebarOpen && (
              <div className="overflow-hidden flex-1">
                <p className="text-sm font-bold text-brand-text truncate">{user?.name}</p>
                <p className="text-[10px] font-semibold text-slate-500 truncate">{user?.callsign} • {user?.role}</p>
              </div>
            )}
          </div>
          {sidebarOpen && (
            <button 
              onClick={handleLogout}
              className="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-semibold text-red-400 hover:text-red-300 bg-red-950/20 hover:bg-red-950/30 border border-red-900/30 hover:border-red-900/50 rounded-xl transition-all"
            >
              <LogOut className="w-3.5 h-3.5" />
              <span>Log Out</span>
            </button>
          )}
        </div>
      </aside>

      {/* Main Container */}
      <div className="flex-1 flex flex-col h-full overflow-hidden">
        {/* Top Navbar */}
        <header className="h-16 border-b border-brand-border bg-brand-card/80 backdrop-blur-md flex items-center justify-between px-8 z-10">
          <div className="flex items-center gap-4">
            <button 
              onClick={() => dispatch(toggleSidebar())}
              className="btn btn-sm btn-circle btn-ghost text-slate-600 hover:bg-slate-100"
            >
              <Menu className="w-5 h-5" />
            </button>
            <div className="flex items-center gap-2 text-xs font-semibold px-3 py-1 bg-brand-bg border border-brand-border text-slate-600">
              <Database className="w-3.5 h-3.5 text-success" />
              <span>MySQL: Connected</span>
            </div>
          </div>
          
          <div className="flex items-center gap-4">
            <span className="text-xs font-semibold px-3 py-1 bg-primary/10 rounded-full text-primary">
              QRV Staff Portal
            </span>
          </div>
        </header>

        {/* Content Page (Scrollable) */}
        <main className="flex-1 overflow-y-auto bg-brand-bg p-8">
          <Routes>
            <Route path="/" element={<EvaluatorForm />} />
            <Route path="/dashboard" element={<Dashboard />} />
            <Route path="/missions" element={isMissionManager ? <Missions /> : <Navigate to="/" replace />} />
          </Routes>
        </main>
      </div>
    </div>
  )
}

function App() {
  return (
    <BrowserRouter>
      <AuthInitializer>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route 
            path="/*" 
            element={
              <ProtectedRoute>
                <MainLayout />
              </ProtectedRoute>
            } 
          />
        </Routes>
      </AuthInitializer>
    </BrowserRouter>
  )
}

export default App
