import React from 'react'
import { BrowserRouter, Routes, Route, Link, useLocation } from 'react-router-dom'
import { useSelector, useDispatch } from 'react-redux'
import { toggleSidebar } from './store/slices/appSlice'
import { 
  LayoutDashboard, 
  Menu, 
  Database,
  PlaneTakeoff,
  ClipboardCheck
} from 'lucide-react'

// Import Pages
import Dashboard from './pages/Dashboard'
import EvaluatorForm from './pages/EvaluatorForm'

import { API_BASE_URL } from './config'

function MainLayout() {
  const dispatch = useDispatch()
  const location = useLocation()
  const { user, sidebarOpen } = useSelector((state) => state.app)

  const menuItems = [
    { name: 'Flight Evaluator', path: '/', icon: ClipboardCheck },
    { name: 'Dashboard', path: '/dashboard', icon: LayoutDashboard },
  ]

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

        {/* User Info */}
        <div className="p-4 border-t border-brand-border flex items-center gap-3">
          <div className="avatar">
            <div className="w-10 rounded-xl border border-brand-border bg-brand-bg">
              <img src={user.avatar} alt="avatar" />
            </div>
          </div>
          {sidebarOpen && (
            <div className="overflow-hidden">
              <p className="text-sm font-bold text-brand-text truncate">{user.username}</p>
              <p className="text-xs text-slate-500 truncate">{user.role}</p>
            </div>
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
          </Routes>
        </main>
      </div>
    </div>
  )
}

function App() {
  return (
    <BrowserRouter>
      <MainLayout />
    </BrowserRouter>
  )
}

export default App
