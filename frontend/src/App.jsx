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

function MainLayout() {
  const dispatch = useDispatch()
  const location = useLocation()
  const { user, sidebarOpen } = useSelector((state) => state.app)

  const menuItems = [
    { name: 'Dashboard', path: '/', icon: LayoutDashboard },
    { name: 'Flight Evaluator', path: '/evaluator', icon: ClipboardCheck },
  ]

  return (
    <div className="flex h-screen overflow-hidden bg-slate-950">
      {/* Sidebar */}
      <aside className={`bg-slate-900 border-r border-slate-800 transition-all duration-300 flex flex-col justify-between z-20 ${sidebarOpen ? 'w-64' : 'w-20'}`}>
        <div>
          {/* Logo Brand */}
          <div className="flex items-center gap-3 px-6 py-5 border-b border-slate-800">
            <PlaneTakeoff className="w-8 h-8 text-primary flex-shrink-0" />
            {sidebarOpen && (
              <span className="text-xl font-bold tracking-wider text-white">ORYXPACE</span>
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
                  className={`flex items-center gap-4 px-4 py-3 rounded-xl transition-all duration-200 ${isActive ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'text-slate-400 hover:bg-slate-800 hover:text-white'}`}
                >
                  <Icon className="w-5 h-5 flex-shrink-0" />
                  {sidebarOpen && <span className="font-medium text-sm">{item.name}</span>}
                </Link>
              )
            })}
          </nav>
        </div>

        {/* User Info */}
        <div className="p-4 border-t border-slate-800 flex items-center gap-3">
          <div className="avatar">
            <div className="w-10 rounded-xl border border-slate-700 bg-slate-850">
              <img src={user.avatar} alt="avatar" />
            </div>
          </div>
          {sidebarOpen && (
            <div className="overflow-hidden">
              <p className="text-sm font-bold text-white truncate">{user.username}</p>
              <p className="text-xs text-slate-500 truncate">{user.role}</p>
            </div>
          )}
        </div>
      </aside>

      {/* Main Container */}
      <div className="flex-1 flex flex-col h-full overflow-hidden">
        {/* Top Navbar */}
        <header className="h-16 border-b border-slate-800 bg-slate-900/60 backdrop-blur-md flex items-center justify-between px-8 z-10">
          <div className="flex items-center gap-4">
            <button 
              onClick={() => dispatch(toggleSidebar())}
              className="btn btn-sm btn-circle btn-ghost text-slate-300 hover:bg-slate-800"
            >
              <Menu className="w-5 h-5" />
            </button>
            <div className="flex items-center gap-2 text-xs font-semibold px-3 py-1 bg-slate-950 border border-slate-800 rounded-full text-slate-400">
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
        <main className="flex-1 overflow-y-auto bg-slate-950 p-8">
          <Routes>
            <Route path="/" element={<Dashboard />} />
            <Route path="/evaluator" element={<EvaluatorForm />} />
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
