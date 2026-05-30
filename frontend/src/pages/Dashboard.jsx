import React, { useState, useEffect } from 'react'
import axios from 'axios'
import { LayoutDashboard, Users, Ticket, PlaneTakeoff, Bell, CheckCircle2, Clock } from 'lucide-react'
import { API_BASE_URL } from '../config'

function Dashboard() {
  const [backendMessage, setBackendMessage] = useState('Loading database statistics...')
  const [connectionStatus, setConnectionStatus] = useState('connecting')
  const [stats, setStats] = useState({
    total_pireps: 0,
    approved_pireps: 0,
    total_pilots: 0,
    total_flight_time_hours: 0,
    recent_flights: []
  })

  useEffect(() => {
    // Fetch live statistics from Django MySQL API
    axios.get(`${API_BASE_URL}/api/stats/`)
      .then(response => {
        setStats(response.data)
        setBackendMessage(`Successfully loaded MySQL tables. Connected directly to your Oryxie Bot Database!`)
        setConnectionStatus('connected')
      })
      .catch(error => {
        setBackendMessage('Could not connect to Django API. Start the server using start_app.bat!')
        setConnectionStatus('failed')
        console.error(error)
      })
  }, [])

  return (
    <div className="space-y-6 animate-fade-in">
      {/* Header */}
      <div className="flex items-center justify-between gap-4 flex-wrap">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-white">Workspace Dashboard</h1>
          <p className="text-slate-400 mt-1">Manage your virtual airline operations and Discord bot sync.</p>
        </div>
        <div className="bg-slate-900/30 border border-slate-800/60 p-3 px-5 rounded-2xl shadow-xl flex items-center justify-center">
          <img 
            src={`${API_BASE_URL}/static/Qatar_virtual_logo_white.PNG`} 
            alt="Qatar Virtual Logo" 
            className="h-12 w-auto object-contain"
            onError={(e) => { e.target.style.display = 'none'; }} /* Fallback if server is starting */
          />
        </div>
      </div>

      {/* Backend Status Alert */}
      <div className={`alert ${connectionStatus === 'connected' ? 'alert-success' : connectionStatus === 'failed' ? 'alert-error' : 'alert-info'} bg-slate-900 border border-slate-800 shadow-lg rounded-2xl`}>
        {connectionStatus === 'connected' ? (
          <CheckCircle2 className="text-success w-6 h-6" />
        ) : (
          <Bell className="text-info w-6 h-6 animate-bounce" />
        )}
        <div>
          <h3 className="font-bold text-white">Django MySQL API Connection</h3>
          <div className="text-xs text-slate-300">{backendMessage}</div>
        </div>
      </div>

      {/* Stats Panel (Real Data!) */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Total PIREPs */}
        <div className="card bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-slate-400">Total Filed PIREPs</p>
              <h4 className="text-3xl font-bold text-white mt-1">{stats.total_pireps}</h4>
            </div>
            <div className="p-3 bg-primary/10 rounded-xl text-primary">
              <PlaneTakeoff className="w-6 h-6" />
            </div>
          </div>
          <div className="mt-4 text-xs text-success flex items-center gap-1">
            <span className="font-bold">{stats.approved_pireps}</span> <span className="text-slate-500">approved flights</span>
          </div>
        </div>

        {/* Total Pilots */}
        <div className="card bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-slate-400">Active Crew Pilots</p>
              <h4 className="text-3xl font-bold text-white mt-1">{stats.total_pilots}</h4>
            </div>
            <div className="p-3 bg-secondary/10 rounded-xl text-secondary">
              <Users className="w-6 h-6" />
            </div>
          </div>
          <div className="mt-4 text-xs text-slate-500">
            Registered pilot callsigns synced in database
          </div>
        </div>

        {/* Total Flight Hours */}
        <div className="card bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-slate-400">Total Fleet Hours</p>
              <h4 className="text-3xl font-bold text-white mt-1">{stats.total_flight_time_hours} hrs</h4>
            </div>
            <div className="p-3 bg-accent/10 rounded-xl text-accent">
              <Clock className="w-6 h-6" />
            </div>
          </div>
          <div className="mt-4 text-xs text-slate-500">
            Total approved hours from accepted flight logs
          </div>
        </div>
      </div>

      {/* Flight Log Activity (Real Database Logs!) */}
      <div className="card bg-slate-900 border border-slate-800 rounded-2xl shadow-xl">
        <div className="p-6 border-b border-slate-800 flex justify-between items-center">
          <h2 className="text-lg font-bold text-white">Live Crew Operations (MySQL Logs)</h2>
          <span className="text-xs px-2 py-1 bg-slate-950 border border-slate-800 rounded-lg text-slate-400">
            Latest 5 entries
          </span>
        </div>
        <div className="overflow-x-auto p-4">
          <table className="table w-full text-slate-300">
            <thead>
              <tr className="border-b border-slate-800 text-slate-400">
                <th>Pilot</th>
                <th>Flight No.</th>
                <th>Route</th>
                <th>Aircraft</th>
                <th>Flight Duration</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              {stats.recent_flights && stats.recent_flights.length > 0 ? (
                stats.recent_flights.map((flight, idx) => (
                  <tr key={idx} className="border-b border-slate-800 hover:bg-slate-800/40">
                    <td className="font-semibold text-white">{flight.pilot_name || 'Unknown Pilot'}</td>
                    <td className="font-mono text-primary">{flight.flightnum}</td>
                    <td className="font-semibold">{flight.departure} ➔ {flight.arrival}</td>
                    <td>{flight.aircraft_name || 'N/A'}</td>
                    <td>
                      <span className="badge badge-ghost badge-sm text-slate-300 py-2.5 px-3 border border-slate-800">
                        {flight.formatted_flighttime}
                      </span>
                    </td>
                    <td className="text-xs text-slate-400">{flight.date}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6" className="text-center py-8 text-slate-500">
                    No flight reports (PIREPs) found in the database.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export default Dashboard
