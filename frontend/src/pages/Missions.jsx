import React, { useState, useEffect, useMemo } from 'react'
import axios from 'axios'
import { API_BASE_URL } from '../config'
import { 
  Calendar, 
  Plus, 
  Edit2, 
  MessageSquare, 
  Image as ImageIcon, 
  Clock, 
  Eye, 
  CheckCircle2, 
  AlertCircle,
  FileText,
  Hash,
  Sparkles,
  Link,
  ChevronDown
} from 'lucide-react'

// Predefined operational Discord channels list
const OPERATIONAL_CHANNELS = [
  { id: '1325788690227920981', name: '📢 mission' },
  { id: '1325788690227921000', name: '📢 event-test' }
]

// Simple helper to parse comprehensive Discord Markdown and Custom Emojis into JSX/HTML
function parseDiscordMarkdown(text) {
  if (!text) return ''

  // 1. Escaping raw HTML tags to prevent layout break or script injection
  let html = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  // 2. Escape character placeholders (e.g. \* -> placeholder)
  const escapes = []
  html = html.replace(/\\(.)/g, (match, char) => {
    escapes.push(char)
    return `\uFFFC${escapes.length - 1}\uFFFC`
  })

  // 3. Multi-line Code Blocks (```code```)
  html = html.replace(/```(?:[a-zA-Z0-9_-]+\n)?([\s\S]*?)```/g, (match, code) => {
    return `<pre class="bg-[#1e1f22] p-2.5 rounded-md font-mono text-[11px] text-[#dbdee1] border border-slate-900 overflow-x-auto whitespace-pre my-1.5">${code}</pre>`
  })

  // 4. Inline Code (`code`)
  html = html.replace(/`([^`\n]+)`/g, '<code class="bg-[#1e1f22] px-1 py-0.5 rounded font-mono text-[11px] text-[#e3e5e8] border border-slate-900">$1</code>')

  // 5. Blockquotes (>>> for multi-line, > for single-line)
  let lines = html.split('\n')
  let inMultiQuote = false
  lines = lines.map((line) => {
    if (inMultiQuote) {
      return `<span class="border-l-[4px] border-[#4e5058] pl-3 text-[#949ba4] block my-0.5">${line}</span>`
    }
    if (line.startsWith('&gt;&gt;&gt; ')) {
      inMultiQuote = true
      return `<span class="border-l-[4px] border-[#4e5058] pl-3 text-[#949ba4] block mt-1.5 mb-0.5">${line.substring(13)}</span>`
    }
    if (line.startsWith('&gt; ')) {
      return `<span class="border-l-[4px] border-[#4e5058] pl-3 text-[#949ba4] block my-1">${line.substring(5)}</span>`
    }
    return line
  })
  html = lines.join('\n')

  // 6. Masked Links: [text](url)
  html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-[#00a8fc] hover:underline">$1</a>')

  // 7. Plain Links: https://...
  html = html.replace(/(?<!href="|src=")(https?:\/\/[^\s<]+)/g, (match, url) => {
    if (url.endsWith('">') || url.endsWith('"')) return match
    return `<a href="${url}" target="_blank" rel="noopener noreferrer" class="text-[#00a8fc] hover:underline">${url}</a>`
  })

  // 8. Mentions: Channel <#id>, User/Role <@id>
  html = html.replace(/&lt;#(\d+)&gt;/g, (match, id) => {
    const name = id === '1325788690227920981' ? 'mission' : (OPERATIONAL_CHANNELS.find(c => c.id === id)?.name || id)
    const cleanName = String(name).replace(/[^a-zA-Z0-9-]/g, '').toLowerCase()
    return `<span class="bg-[#5865f2]/15 text-[#c9cdfb] font-semibold px-1 rounded hover:bg-[#5865f2] hover:text-white transition-colors cursor-pointer">#${cleanName || id}</span>`
  })
  html = html.replace(/&lt;@(!|&amp;)?(\d+)&gt;/g, (match, prefix, id) => {
    const label = prefix === '&amp;' ? `role-${id}` : `user-${id}`
    return `<span class="bg-[#5865f2]/30 text-[#dee0fc] font-semibold px-1 rounded hover:bg-[#5865f2] hover:text-white transition-colors cursor-pointer">@${label}</span>`
  })

  // 9. Custom Server Emojis: <:name:id> or <a:name:id>
  html = html.replace(/&lt;:([a-zA-Z0-9_]+):(\d+)&gt;/g, '<img class="inline-block w-5 h-5 align-bottom mx-0.5" src="https://cdn.discordapp.com/emojis/$2.png" alt=":$1:" title=":$1:" />')
  html = html.replace(/&lt;a:([a-zA-Z0-9_]+):(\d+)&gt;/g, '<img class="inline-block w-5 h-5 align-bottom mx-0.5" src="https://cdn.discordapp.com/emojis/$2.gif" alt=":$1:" title=":$1:" />')

  // 10. Spoiler Tags (||text||)
  html = html.replace(/\|\|(.*?)\|\|/g, '<span class="bg-[#1e1f22] text-transparent hover:text-[#dbdee1] active:text-[#dbdee1] rounded px-1 transition-all cursor-pointer select-none" title="Spoiler: hover or click to reveal">$1</span>')

  // 11. Underline Bold Italics (__***text***__)
  html = html.replace(/__\*\*\*(.*?)\*\*\*__/g, '<u><strong><em>$1</em></strong></u>')

  // 12. Underline Bold (__**text**__)
  html = html.replace(/__\*\*(.*?)\*\*__/g, '<u><strong>$1</strong></u>')

  // 13. Underline Italics (__*text*__)
  html = html.replace(/__\*(.*?)\*__/g, '<u><em>$1</em></u>')

  // 14. Bold Italics (***text***)
  html = html.replace(/\*\*\*(.*?)\*\*\*/g, '<strong><em>$1</em></strong>')

  // 15. Bold (**text**)
  html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')

  // 16. Underline (__text__)
  html = html.replace(/__(.*?)__/g, '<u>$1</u>')

  // 17. Italics (*text* or _text_)
  html = html.replace(/\*(.*?)\*/g, '<em>$1</em>')
  html = html.replace(/_([^_]+)_/g, '<em>$1</em>')

  // 18. Strikethrough (~~text~~)
  html = html.replace(/~~(.*?)~~/g, '<del class="line-through text-slate-400">$1</del>')

  // 19. Headers and lists (splitting by line)
  lines = html.split('\n')
  lines = lines.map(line => {
    if (line.startsWith('### ')) {
      return `<h3 class="text-xs font-bold text-[#f2f3f5] mt-2 mb-1">${line.substring(4)}</h3>`
    }
    if (line.startsWith('## ')) {
      return `<h2 class="text-sm font-bold text-[#f2f3f5] mt-2.5 mb-1">${line.substring(3)}</h2>`
    }
    if (line.startsWith('# ')) {
      return `<h1 class="text-base font-extrabold text-[#f2f3f5] mt-3 mb-1">${line.substring(2)}</h1>`
    }
    if (line.startsWith('- ') || line.startsWith('* ')) {
      return `<li class="ml-4 list-disc text-slate-300 my-0.5">${line.substring(2)}</li>`
    }
    const numMatch = line.match(/^(\d+)\.\s+(.*)/)
    if (numMatch) {
      return `<li class="ml-4 list-decimal text-slate-300 my-0.5">${numMatch[2]}</li>`
    }
    return line
  })
  html = lines.join('\n')

  // 20. Convert newlines to breaks
  html = html.replace(/\n/g, '<br/>')

  // 21. Restore escaped character literals
  html = html.replace(/\uFFFC(\d+)\uFFFC/g, (match, idx) => {
    const char = escapes[parseInt(idx, 10)]
    if (char === '*') return '&#42;'
    if (char === '_') return '&#95;'
    if (char === '~') return '&#126;'
    if (char === '`') return '&#96;'
    if (char === '|') return '&#124;'
    if (char === '\\') return '&#92;'
    return char
  })

  return <span dangerouslySetInnerHTML={{ __html: html }} />
}

// Convert standard names or hex color codes to actual CSS/hex colors
function getHexColor(colorStr) {
  if (!colorStr) return '#FFD700' // Gold default
  
  const clean = colorStr.toLowerCase().trim()
  const colorMap = {
    red: '#EF4444',
    blue: '#3B82F6',
    green: '#10B981',
    gold: '#F59E0B',
    purple: '#8B5CF6',
    orange: '#F97316',
    yellow: '#FBBF24',
    pink: '#EC4899',
    teal: '#14B8A6'
  }
  
  if (colorMap[clean]) return colorMap[clean]
  if (clean.startsWith('#')) return colorStr
  
  return '#F59E0B' // Fallback
}

function Missions() {
  const [missions, setMissions] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [modalOpen, setModalOpen] = useState(false)
  const [editingId, setEditingId] = useState(null)
  const [visibleCount, setVisibleCount] = useState(5)
  
  // Form States
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [imageUrl, setImageUrl] = useState('')
  const [footerText, setFooterText] = useState('')
  const [color, setColor] = useState('gold')
  const [postDate, setPostDate] = useState('')
  const [postTime, setPostTime] = useState('')
  const [channelId, setChannelId] = useState(OPERATIONAL_CHANNELS[0].id)
  const [isCustomChannel, setIsCustomChannel] = useState(false)
  const [customChannelVal, setCustomChannelVal] = useState('')
  // Scoring/multipliers disabled as they are handled in backend/cogs
  
  const [submitting, setSubmitting] = useState(false)

  // Fetch missions from Django database
  const fetchMissions = () => {
    setLoading(true)
    axios.get(`${API_BASE_URL}/api/missions/events/`)
      .then(response => {
        setMissions(response.data)
        setVisibleCount(5)
        setLoading(false)
      })
      .catch(err => {
        console.error(err)
        setError('Failed to load scheduled missions from crew database.')
        setLoading(false)
      })
  }

  useEffect(() => {
    fetchMissions()
  }, [])

  // Parse form date/time to ISO String for backend
  const getIsoPostTime = (dateStr, timeStr) => {
    try {
      const dateParts = dateStr.split(':')
      const timeParts = timeStr.split(':')
      if (dateParts.length !== 3 || timeParts.length !== 2) {
        throw new Error('Date must be in DD:MM:YYYY format and time in HH:MM format')
      }
      const day = parseInt(dateParts[0], 10)
      const month = parseInt(dateParts[1], 10) - 1 // JS Date month is 0-indexed
      const year = parseInt(dateParts[2], 10)
      const hour = parseInt(timeParts[0], 10)
      const minute = parseInt(timeParts[1], 10)
      
      const dt = new Date(Date.UTC(year, month, day, hour, minute))
      if (isNaN(dt.getTime())) throw new Error('Invalid Date input')
      return dt.toISOString()
    } catch (e) {
      alert(e.message)
      return null
    }
  }

  // Pre-fill form when editing
  const handleEditClick = (mission) => {
    setEditingId(mission.id)
    setTitle(mission.title)
    setDescription(mission.description)
    setImageUrl(mission.image_url || '')
    setFooterText(mission.footer_text || '')
    setColor(mission.color || 'gold')
    // Scoring and multiplier parameters omitted
    
    // Parse post_time to date and time inputs
    if (mission.post_time) {
      const dt = new Date(mission.post_time)
      const day = String(dt.getUTCDate()).padStart(2, '0')
      const month = String(dt.getUTCMonth() + 1).padStart(2, '0')
      const year = dt.getUTCFullYear()
      const hour = String(dt.getUTCHours()).padStart(2, '0')
      const minute = String(dt.getUTCMinutes()).padStart(2, '0')
      
      setPostDate(`${day}:${month}:${year}`)
      setPostTime(`${hour}:${minute}`)
    }

    // Set channel
    const predefinedExists = OPERATIONAL_CHANNELS.some(c => c.id === String(mission.channel_id))
    if (predefinedExists) {
      setChannelId(String(mission.channel_id))
      setIsCustomChannel(false)
    } else {
      setChannelId('custom')
      setIsCustomChannel(true)
      setCustomChannelVal(String(mission.channel_id))
    }

    setModalOpen(true)
  }

  const handleOpenAddModal = () => {
    setEditingId(null)
    setTitle('')
    setDescription('')
    setImageUrl('')
    setFooterText('')
    setColor('gold')
    // Form resets for scoring omitted
    
    // Default to current date/time
    const tomorrow = new Date()
    tomorrow.setUTCDate(tomorrow.getUTCDate() + 1)
    const day = String(tomorrow.getUTCDate()).padStart(2, '0')
    const month = String(tomorrow.getUTCMonth() + 1).padStart(2, '0')
    const year = tomorrow.getUTCFullYear()
    setPostDate(`${day}:${month}:${year}`)
    setPostTime('12:00')
    
    setChannelId(OPERATIONAL_CHANNELS[0].id)
    setIsCustomChannel(false)
    setCustomChannelVal('')
    setModalOpen(true)
  }

  const handleChannelSelectChange = (e) => {
    const val = e.target.value
    setChannelId(val)
    if (val === 'custom') {
      setIsCustomChannel(true)
    } else {
      setIsCustomChannel(false)
    }
  }

  const handleFormSubmit = (e) => {
    e.preventDefault()
    
    const finalPostTime = getIsoPostTime(postDate, postTime)
    if (!finalPostTime) return

    const finalChannelId = isCustomChannel ? customChannelVal : channelId
    if (!finalChannelId || !/^\d+$/.test(finalChannelId)) {
      alert('Please provide a valid numeric Discord Channel ID.')
      return
    }

    const payload = {
      title,
      description,
      image_url: imageUrl || null,
      footer_text: footerText || null,
      color,
      flight_numbers: 'N/A',
      custom_emojis: 'N/A',
      multiplier: 0,
      deadline_hours: 0,
      channel_id: parseInt(finalChannelId, 10),
      post_time: finalPostTime
    }

    setSubmitting(true)
    
    const request = editingId 
      ? axios.put(`${API_BASE_URL}/api/missions/events/${editingId}/`, payload)
      : axios.post(`${API_BASE_URL}/api/missions/events/`, payload)

    request
      .then(() => {
        setSubmitting(false)
        setModalOpen(false)
        fetchMissions()
        alert(editingId ? 'Mission updated successfully!' : 'New mission scheduled successfully!')
      })
      .catch(err => {
        console.error(err)
        alert('Failed to save scheduled mission. Check your inputs or server status.')
        setSubmitting(false)
      })
  }

  const getChannelName = (chanId) => {
    const match = OPERATIONAL_CHANNELS.find(c => c.id === String(chanId))
    return match ? match.name : `#${chanId}`
  }

  if (loading && missions.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <span className="loading loading-spinner loading-lg text-primary"></span>
        <p className="text-brand-subtext font-medium animate-pulse">Loading operations schedules from database...</p>
      </div>
    )
  }

  return (
    <div className="space-y-6 max-w-6xl mx-auto animate-fade-in pb-16">
      
      {/* Header Title */}
      <div className="flex justify-between items-center gap-4 flex-wrap">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-brand-text">Mission Announcer</h1>
          <p className="text-brand-subtext mt-1">Schedule and publish official virtual airline crew events directly to Discord channels.</p>
        </div>
        
        <button 
          onClick={handleOpenAddModal}
          className="btn btn-primary rounded-xl px-5 font-bold flex items-center justify-center gap-2 shadow-lg shadow-primary/20 hover:scale-[1.01] transition-transform"
        >
          <Plus className="w-5 h-5" />
          Schedule Mission
        </button>
      </div>

      {error && (
        <div className="alert alert-error bg-white border border-[#ead9df] shadow-xl rounded-2xl flex gap-4 max-w-2xl mx-auto">
          <AlertCircle className="w-8 h-8 text-error flex-shrink-0" />
          <div>
            <h3 className="font-bold text-error">System Error</h3>
            <p className="text-xs text-slate-700 mt-1">{error}</p>
          </div>
        </div>
      )}

      {/* Scheduled Events Table */}
      <div className="card bg-brand-card border border-brand-border shadow-xl rounded-2xl overflow-hidden">
        <div className="p-6 border-b border-brand-border flex justify-between items-center bg-brand-bg/30">
          <div className="flex items-center gap-2 text-slate-700 font-bold tracking-wider text-xs">
            <Calendar className="w-4 h-4 text-primary" />
            <span>ALL SCHEDULED DISCORD EVENTS</span>
          </div>
          <span className="badge badge-sm font-semibold text-slate-500 bg-brand-bg border border-brand-border py-2 px-3">
            {missions.length} events logged
          </span>
        </div>

        <div className="overflow-x-auto">
          <table className="table w-full text-slate-700">
            <thead>
              <tr className="border-b border-brand-border text-slate-600 bg-brand-bg/10">
                <th>Event Title</th>
                <th>Discord Channel</th>
                <th>Posting Date & Time (UTC)</th>

                <th>Status</th>
                <th className="text-center">Action</th>
              </tr>
            </thead>
            <tbody>
              {missions.length > 0 ? (
                missions.slice(0, visibleCount).map((mission) => {
                  const isPosted = mission.is_posted === 1
                  const postTimeObj = new Date(mission.post_time)
                  
                  return (
                    <tr key={mission.id} className="border-b border-brand-border hover:bg-brand-bg/30 transition-colors">
                      <td>
                        <div className="font-bold text-slate-900 text-sm">{mission.title}</div>
                        <div className="text-[10px] text-slate-500 mt-0.5 truncate max-w-xs">{mission.description}</div>
                      </td>
                      <td className="font-medium text-slate-800 text-xs">
                        {getChannelName(mission.channel_id)}
                      </td>
                      <td className="text-xs font-semibold text-slate-800">
                        <div className="flex items-center gap-1.5">
                          <Clock className="w-3.5 h-3.5 text-slate-400" />
                          <span>
                            {postTimeObj.toUTCString().replace('GMT', 'UTC')}
                          </span>
                        </div>
                      </td>

                      <td>
                        {isPosted ? (
                          <span className="badge badge-success font-bold text-[10px] py-2 px-2.5 flex items-center gap-1">
                            <CheckCircle2 className="w-3 h-3" />
                            POSTED
                          </span>
                        ) : (
                          <span className="badge badge-warning font-bold text-[10px] py-2 px-2.5 flex items-center gap-1">
                            <Clock className="w-3 h-3 animate-spin" />
                            SCHEDULED
                          </span>
                        )}
                      </td>
                      <td className="text-center">
                        <button
                          onClick={() => handleEditClick(mission)}
                          className="btn btn-xs btn-outline border-slate-350 text-slate-600 rounded-lg hover:bg-primary hover:border-primary hover:text-white"
                          title="Edit Scheduled Event Details"
                        >
                          <Edit2 className="w-3 h-3 mr-1" />
                          Edit
                        </button>
                      </td>
                    </tr>
                  )
                })
              ) : (
                <tr>
                  <td colSpan="5" className="text-center py-12 text-slate-400 font-medium">
                    No scheduled events or missions found in the MySQL database.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        {missions.length > visibleCount && (
          <div className="p-4 border-t border-brand-border flex justify-center bg-brand-bg/10">
            <button 
              type="button"
              onClick={() => setVisibleCount(missions.length)}
              className="btn btn-sm btn-outline border-slate-350 text-slate-600 rounded-lg hover:bg-primary hover:text-white font-bold transition-all px-6 py-2"
            >
              Show More
            </button>
          </div>
        )}
      </div>

      {/* Add/Edit Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 md:p-8 animate-fade-in overflow-y-auto">
          <div className="bg-white rounded-3xl shadow-2xl flex flex-col w-full max-w-5xl mx-auto overflow-hidden border border-slate-200 max-h-[90vh] lg:max-h-[85vh]">
            
            {/* Modal Header */}
            <div className="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
              <div className="flex items-center gap-2">
                <Sparkles className="w-5 h-5 text-primary" />
                <h3 className="font-extrabold text-brand-text text-base">
                  {editingId ? 'Edit Scheduled Mission Event' : 'Schedule New Mission Event'}
                </h3>
              </div>
              <button
                type="button"
                onClick={() => setModalOpen(false)}
                className="btn btn-sm btn-circle btn-ghost text-slate-500 hover:bg-slate-200"
              >
                ✕
              </button>
            </div>

            {/* Modal Body (Scrollable Split screen) */}
            <div className="flex-grow overflow-y-auto p-6 grid grid-cols-1 lg:grid-cols-12 gap-8">
              
              {/* Left Side: Form Editor (span 7) */}
              <form onSubmit={handleFormSubmit} className="lg:col-span-7 space-y-4">
                
                {/* Event Title */}
                <div className="form-control w-full">
                  <label className="label py-1">
                    <span className="label-text text-brand-text font-bold text-xs">MISSION TITLE</span>
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. Welcome to Prague"
                    value={title}
                    onChange={e => setTitle(e.target.value)}
                    className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                    required
                    maxLength={200}
                  />
                </div>

                {/* Description Textarea */}
                <div className="form-control w-full">
                  <label className="label py-1">
                    <span className="label-text text-brand-text font-bold text-xs">DESCRIPTION (SUPPORTS BOLD **TEXT**)</span>
                  </label>
                  <textarea
                    placeholder="Enter detailed event description, scheduling guidelines, or crew briefings..."
                    value={description}
                    onChange={e => setDescription(e.target.value)}
                    className="textarea textarea-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full p-4 h-32 leading-relaxed"
                    required
                    maxLength={2000}
                  />
                </div>

                {/* Target Channel Dropdown & Inputs */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="form-control w-full">
                    <label className="label py-1">
                      <span className="label-text text-brand-text font-bold text-xs">DISCORD TARGET CHANNEL</span>
                    </label>
                    <select
                      value={channelId}
                      onChange={handleChannelSelectChange}
                      className="select select-bordered rounded-xl border-slate-350 focus:border-primary bg-brand-card text-slate-700 font-semibold w-full"
                    >
                      {OPERATIONAL_CHANNELS.map(c => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                      ))}
                      <option value="custom">✍️ Custom Channel ID...</option>
                    </select>
                  </div>

                  {isCustomChannel && (
                    <div className="form-control w-full animate-slide-down">
                      <label className="label py-1">
                        <span className="label-text text-brand-text font-bold text-xs">CUSTOM CHANNEL ID</span>
                      </label>
                      <input
                        type="text"
                        placeholder="e.g. 1402291473302421555"
                        value={customChannelVal}
                        onChange={e => setCustomChannelVal(e.target.value.replace(/\D/g, ''))}
                        className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                        required={isCustomChannel}
                      />
                    </div>
                  )}
                </div>

                {/* Date & Time Settings */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="form-control w-full">
                    <label className="label py-1">
                      <span className="label-text text-brand-text font-bold text-xs">POSTING DATE (DD:MM:YYYY)</span>
                    </label>
                    <input
                      type="text"
                      placeholder="e.g. 25:12:2024"
                      value={postDate}
                      onChange={e => setPostDate(e.target.value)}
                      className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                      required
                    />
                  </div>

                  <div className="form-control w-full">
                    <label className="label py-1">
                      <span className="label-text text-brand-text font-bold text-xs">POSTING TIME (HH:MM UTC)</span>
                    </label>
                    <input
                      type="text"
                      placeholder="e.g. 18:00"
                      value={postTime}
                      onChange={e => setPostTime(e.target.value)}
                      className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                      required
                    />
                  </div>
                </div>

                {/* Media Links */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div className="form-control w-full">
                    <label className="label py-1">
                      <span className="label-text text-brand-text font-bold text-xs">IMAGE URL (OPTIONAL)</span>
                    </label>
                    <input
                      type="text"
                      placeholder="https://i.imgur.com/image.png"
                      value={imageUrl}
                      onChange={e => setImageUrl(e.target.value)}
                      className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                    />
                  </div>

                  <div className="form-control w-full">
                    <label className="label py-1">
                      <span className="label-text text-brand-text font-bold text-xs">ACCENT COLOR</span>
                    </label>
                    <input
                      type="text"
                      placeholder="gold, red, blue, or #FFD700"
                      value={color}
                      onChange={e => setColor(e.target.value)}
                      className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                    />
                  </div>
                </div>

                {/* Footer Text */}
                <div className="form-control w-full">
                  <label className="label py-1">
                    <span className="label-text text-brand-text font-bold text-xs">FOOTER CAPTION TEXT (OPTIONAL)</span>
                  </label>
                  <input
                    type="text"
                    placeholder="e.g. ✨ Let the magic inspire your journey! ✨"
                    value={footerText}
                    onChange={e => setFooterText(e.target.value)}
                    className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary border-slate-350 w-full"
                  />
                </div>



                {/* Form Buttons */}
                <div className="flex gap-4 pt-4">
                  <button
                    type="submit"
                    disabled={submitting}
                    className="btn btn-primary flex-1 rounded-xl font-bold h-12"
                  >
                    {submitting ? (
                      <span className="loading loading-spinner"></span>
                    ) : editingId ? (
                      'Save Scheduled Changes'
                    ) : (
                      'Save & Schedule Mission'
                    )}
                  </button>
                  <button
                    type="button"
                    onClick={() => setModalOpen(false)}
                    className="btn btn-outline border-slate-350 text-slate-600 hover:bg-slate-100 rounded-xl font-bold h-12"
                  >
                    Cancel
                  </button>
                </div>

              </form>

              {/* Right Side: Discord Embed Live Preview (span 5) */}
              <div className="lg:col-span-5 flex flex-col space-y-3">
                <span className="text-xs font-bold text-slate-500 uppercase tracking-widest pl-1 block">
                  🛡️ Live Discord Embed Mockup
                </span>
                
                <div className="bg-[#1e1f22] p-4 rounded-xl flex-grow flex flex-col min-h-[300px] border border-slate-900 shadow-inner">
                  
                  {/* Mock Channel Header */}
                  <div className="border-b border-[#2b2d31] pb-2 mb-3 flex items-center gap-1.5 text-xs text-[#b5bac1] font-bold">
                    <Hash className="w-3.5 h-3.5 text-[#80848e]" />
                    <span>{getChannelName(isCustomChannel ? customChannelVal : channelId).replace(/[^a-zA-Z0-9-]/g, '')}</span>
                  </div>

                  {/* Embed Container */}
                  <div 
                    className="bg-[#2b2d31] border-l-[4px] rounded-[4px] p-4 max-w-[520px] font-sans text-left text-xs text-[#dbdee1] flex flex-col space-y-2 select-none shadow-md"
                    style={{ borderColor: getHexColor(color) }}
                  >
                    {/* Embed Title */}
                    {title ? (
                      <h4 className="text-[#f2f3f5] font-semibold text-[15px] leading-[18px]">
                        {title}
                      </h4>
                    ) : (
                      <h4 className="text-[#80848e] italic text-[15px] leading-[18px]">
                        No Mission Title Configured...
                      </h4>
                    )}

                    {/* Embed Description */}
                    {description ? (
                      <div className="leading-[18px]">
                        {parseDiscordMarkdown(description)}
                      </div>
                    ) : (
                      <div className="text-[#80848e] italic leading-[18px]">
                        No description briefed yet...
                      </div>
                    )}

                    {/* Embed Image */}
                    {imageUrl && (
                      <div className="mt-2 rounded-[6px] overflow-hidden max-h-[200px] bg-slate-950/20 border border-slate-900/10">
                        <img 
                          src={imageUrl} 
                          alt="Live Preview Embed Media" 
                          className="w-full h-full object-cover"
                          onError={(e) => {
                            e.target.onerror = null;
                            e.target.src = "https://placehold.co/400x200/2b2d31/ffffff?text=Image+Load+Error";
                          }}
                        />
                      </div>
                    )}

                    {/* Embed Footer */}
                    {footerText && (
                      <div className="mt-2 text-[#949ba4] text-[11px] leading-[14px] font-medium border-t border-[#35363c] pt-2">
                        {footerText}
                      </div>
                    )}

                  </div>
                  
                  <div className="mt-auto pt-4 text-[10px] text-[#80848e] font-semibold flex justify-between">
                    <span>* Real-time CSS layout preview</span>
                    <span>100% Client Fidelity</span>
                  </div>

                </div>
              </div>

            </div>

          </div>
        </div>
      )}

    </div>
  )
}

export default Missions
