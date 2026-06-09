import React, { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useDispatch, useSelector } from 'react-redux'
import { setCredentials } from '../store/slices/appSlice'
import { API_BASE_URL } from '../config'
import { Lock, Mail, AlertTriangle, Loader2 } from 'lucide-react'

export default function Login() {
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  
  const navigate = useNavigate()
  const dispatch = useDispatch()
  const { user } = useSelector((state) => state.app)

  // Redirect if already logged in
  useEffect(() => {
    if (user) {
      navigate('/')
    }
  }, [user, navigate])

  const handleSubmit = async (e) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const response = await fetch(`${API_BASE_URL}/api/auth/login/`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email, password }),
      })

      const data = await response.json()

      if (!response.ok) {
        throw new Error(data.error || 'Login failed. Please check your credentials.')
      }

      // Save token to localStorage and update redux store
      localStorage.setItem('token', data.token)
      dispatch(setCredentials({ user: data.user, token: data.token }))
      
      // Redirect to Flight Evaluator (root)
      navigate('/')
    } catch (err) {
      setError(err.message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="min-h-screen w-full flex items-center justify-center bg-slate-950 relative overflow-hidden font-sans">
      {/* Background Graphic elements */}
      <div className="absolute top-[-20%] left-[-10%] w-[50%] h-[60%] rounded-full bg-primary/20 blur-[120px] pointer-events-none" />
      <div className="absolute bottom-[-20%] right-[-10%] w-[50%] h-[60%] rounded-full bg-brand-accent/20 blur-[120px] pointer-events-none" />
      
      {/* Container */}
      <div className="w-full max-w-[440px] px-6 z-10">
        {/* Logo and Header */}
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center p-3 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl shadow-slate-950/20 mb-4">
            <img 
              src={`${API_BASE_URL}/static/Qatar_Virtual_logo.PNG`} 
              alt="Qatar Virtual Logo" 
              className="h-10 w-auto object-contain"
            />
          </div>
          <h1 className="text-2xl font-bold text-white tracking-tight">Oryxpace Crew Portal</h1>
          <p className="text-sm text-slate-400 mt-1">Please sign in with your Crew Center credentials</p>
        </div>

        {/* Card */}
        <div className="bg-slate-900/75 backdrop-blur-xl border border-slate-800/60 rounded-3xl p-8 shadow-2xl shadow-slate-950/50">
          <form onSubmit={handleSubmit} className="space-y-5">
            {error && (
              <div className="p-3.5 rounded-xl bg-red-950/30 border border-red-800/40 text-red-200 text-xs flex items-start gap-2.5">
                <AlertTriangle className="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" />
                <span>{error}</span>
              </div>
            )}

            {/* Email Input */}
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-300 ml-1">Email Address</label>
              <div className="relative group">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 group-focus-within:text-primary transition-colors">
                  <Mail className="w-4.5 h-4.5" />
                </div>
                <input
                  type="email"
                  required
                  placeholder="name@qatarivirtual.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 bg-slate-950/50 focus:bg-slate-950 border border-slate-800 focus:border-primary/70 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                  disabled={loading}
                />
              </div>
            </div>

            {/* Password Input */}
            <div className="space-y-1.5">
              <label className="text-xs font-semibold text-slate-300 ml-1">Password</label>
              <div className="relative group">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500 group-focus-within:text-primary transition-colors">
                  <Lock className="w-4.5 h-4.5" />
                </div>
                <input
                  type="password"
                  required
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full pl-10 pr-4 py-3 bg-slate-950/50 focus:bg-slate-950 border border-slate-800 focus:border-primary/70 rounded-xl text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 transition-all"
                  disabled={loading}
                />
              </div>
            </div>

            {/* Submit Button */}
            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-primary hover:bg-primary-hover active:scale-[0.99] text-white font-medium text-sm rounded-xl transition-all shadow-lg shadow-primary/30 flex items-center justify-center gap-2"
            >
              {loading ? (
                <>
                  <Loader2 className="w-4 h-4 animate-spin" />
                  <span>Authenticating...</span>
                </>
              ) : (
                <span>Sign In</span>
              )}
            </button>
          </form>

          {/* Footer Notice */}
          <div className="mt-6 pt-5 border-t border-slate-800/40 text-center">
            <p className="text-[11px] text-slate-500 leading-normal">
              New pilot? Applications and registrations are managed exclusively on the main Crew Center website. 
              <br />
              <span className="font-semibold text-slate-400 mt-1 block">Oryxpace is staff-only.</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}
