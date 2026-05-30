import React, { useState, useEffect } from 'react'
import axios from 'axios'
import { API_BASE_URL } from '../config'
import { 
  ClipboardCheck, 
  User, 
  Calendar, 
  Plane, 
  FileText, 
  Download, 
  AlertCircle,
  CheckCircle,
  MessageSquare,
  ChevronDown,
  ChevronUp,
  RotateCcw
} from 'lucide-react'

function EvaluatorForm() {
  const [questions, setQuestions] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  
  // Metadata fields
  const [metadata, setMetadata] = useState({
    trainee_name: '',
    trainee_callsign: '',
    instructor_name: 'Bumy [QRV]', // default mock name
    flight_callsign: '',
    flight_date: new Date().toISOString().split('T')[0],
    general_remarks: ''
  })

  // Selected answers state
  // key: question_id, val: { selected_option_index: null, is_other: false, other_text: '', extra_feedback: '', showComments: false }
  const [answers, setAnswers] = useState({})

  // Fetch rubrics
  useEffect(() => {
    axios.get(`${API_BASE_URL}/api/evaluations/questions/`)
      .then(response => {
        setQuestions(response.data)
        
        // Initialize answers state
        const initialAnswers = {}
        response.data.forEach(q => {
          initialAnswers[q.id] = {
            question_id: q.id,
            selected_option_index: null,
            is_other: false,
            other_text: '',
            extra_feedback: '',
            showComments: false
          }
        })
        setAnswers(initialAnswers)
        setLoading(false)
      })
      .catch(err => {
        console.error(err)
        setError('Failed to fetch evaluation templates from Django backend.')
        setLoading(false)
      })
  }, [])

  // Calculate live score
  const calculateScore = () => {
    let totalPoints = 0
    let pointsEarned = 0
    let answeredCount = 0

    questions.forEach(q => {
      totalPoints += q.points || 1
      const ans = answers[q.id]
      if (ans && ans.selected_option_index !== null) {
        answeredCount++
        const isCorrect = !ans.is_other && (ans.selected_option_index === q.correctAnswerIndex)
        if (isCorrect) {
          pointsEarned += q.points || 1
        }
      }
    })

    const percent = totalPoints > 0 ? (pointsEarned / totalPoints) * 100 : 0
    return {
      totalPoints,
      pointsEarned,
      answeredCount,
      percent,
      isPassed: percent >= 75.0
    }
  }

  const handleMetadataChange = (field, val) => {
    setMetadata(prev => ({ ...prev, [field]: val }))
  }

  const handleSelectOption = (qId, optionIdx, isOther = false) => {
    setAnswers(prev => ({
      ...prev,
      [qId]: {
        ...prev[qId],
        selected_option_index: optionIdx,
        is_other: isOther,
        // If switching away from other, clear other text
        other_text: isOther ? prev[qId].other_text : ''
      }
    }))
  }

  const handleOtherTextChange = (qId, text) => {
    setAnswers(prev => ({
      ...prev,
      [qId]: {
        ...prev[qId],
        other_text: text
      }
    }))
  }

  const handleFeedbackChange = (qId, text) => {
    setAnswers(prev => ({
      ...prev,
      [qId]: {
        ...prev[qId],
        extra_feedback: text
      }
    }))
  }

  const toggleComments = (qId) => {
    setAnswers(prev => ({
      ...prev,
      [qId]: {
        ...prev[qId],
        showComments: !prev[qId].showComments
      }
    }))
  }

  const resetForm = () => {
    if (window.confirm('Are you sure you want to reset the current checklist?')) {
      const resetAnswers = {}
      questions.forEach(q => {
        resetAnswers[q.id] = {
          question_id: q.id,
          selected_option_index: null,
          is_other: false,
          other_text: '',
          extra_feedback: '',
          showComments: false
        }
      })
      setAnswers(resetAnswers)
      setMetadata({
        trainee_name: '',
        trainee_callsign: '',
        instructor_name: 'Bumy [QRV]',
        flight_callsign: '',
        flight_date: new Date().toISOString().split('T')[0],
        general_remarks: ''
      })
    }
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    
    // Check if metadata fields are filled
    if (!metadata.trainee_name || !metadata.trainee_callsign || !metadata.flight_callsign) {
      alert('Please fill out all pilot and flight details in the card above.')
      return
    }

    // Check if all questions are answered
    const unanswered = questions.filter(q => answers[q.id]?.selected_option_index === null)
    if (unanswered.length > 0) {
      alert(`Please evaluate all procedures. Unanswered items: ${unanswered.map(u => u.id).join(', ')}`)
      return
    }

    // Check if all 'other' choices have text written
    const emptyOthers = questions.filter(q => {
      const ans = answers[q.id]
      return ans?.is_other && !ans.other_text.trim()
    })
    if (emptyOthers.length > 0) {
      alert(`Please describe what was observed in the "Other" text boxes for: ${emptyOthers.map(o => o.id).join(', ')}`)
      return
    }

    setSubmitting(true)

    // Build API payload
    const payload = {
      ...metadata,
      answers: Object.values(answers).map(ans => ({
        question_id: ans.question_id,
        selected_option_index: ans.selected_option_index,
        is_other: ans.is_other,
        other_text: ans.other_text,
        extra_feedback: ans.extra_feedback
      }))
    }

    // Send payload to Django API to build and stream PDF
    axios({
      url: `${API_BASE_URL}/api/evaluations/generate/`,
      method: 'POST',
      data: payload,
      responseType: 'blob' // Essential to handle binary file stream!
    })
      .then(response => {
        // Create file download link in browser
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        
        const filename = `Evaluation_${metadata.trainee_callsign}_${metadata.flight_date}.pdf`.replace(/\s+/g, '_')
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        
        // Cleanup
        link.parentNode.removeChild(link)
        window.URL.revokeObjectURL(url)
        setSubmitting(false)
      })
      .catch(err => {
        console.error(err)
        alert('An error occurred while generating the PDF report. Please make sure reportlab is installed on the backend.')
        setSubmitting(false)
      })
  }

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[50vh] space-y-4">
        <span className="loading loading-spinner loading-lg text-primary"></span>
        <p className="text-slate-400 font-medium animate-pulse">Fetching checklist templates from flight operations server...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="alert alert-error bg-slate-900 border border-slate-800 shadow-xl rounded-2xl flex gap-4 max-w-2xl mx-auto mt-8">
        <AlertCircle className="w-8 h-8 text-error flex-shrink-0" />
        <div>
          <h3 className="font-bold text-white">System Error</h3>
          <p className="text-xs text-slate-400 mt-1">{error}</p>
          <p className="text-xs text-slate-500 mt-2">Hint: Run start_app.bat to spin up your Django REST server.</p>
        </div>
      </div>
    )
  }

  const scoreStats = calculateScore()

  return (
    <div className="space-y-6 max-w-4xl mx-auto animate-fade-in pb-16">
      
      {/* Header Title */}
      <div className="flex justify-between items-center gap-4 flex-wrap">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-white">Trainee Flight Evaluator</h1>
          <p className="text-slate-400 mt-1">Check replay telemetry and fill out standard procedurals for instant PDF publication.</p>
        </div>
        
        <button 
          onClick={resetForm}
          className="btn btn-sm btn-outline border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-white"
        >
          <RotateCcw className="w-4 h-4 mr-2" />
          Reset Checklist
        </button>
      </div>

      {/* Floating Live Scorecard Widget */}
      <div className={`sticky top-4 z-30 shadow-2xl rounded-2xl border transition-all duration-300 ${
        scoreStats.answeredCount === questions.length
          ? scoreStats.isPassed 
            ? 'bg-emerald-950/90 border-emerald-800 backdrop-blur-md text-white' 
            : 'bg-orange-950/90 border-orange-800 backdrop-blur-md text-white'
          : 'bg-slate-900/95 border-slate-800 backdrop-blur-md text-white'
      } p-4 px-6 flex justify-between items-center flex-wrap gap-4`}>
        <div className="flex items-center gap-4">
          <div className={`p-3 rounded-xl ${
            scoreStats.answeredCount === questions.length
              ? scoreStats.isPassed 
                ? 'bg-emerald-500/25 text-emerald-400' 
                : 'bg-orange-500/25 text-orange-400'
              : 'bg-primary/20 text-primary'
          }`}>
            <ClipboardCheck className="w-6 h-6 animate-pulse" />
          </div>
          <div>
            <h3 className="font-bold text-sm tracking-wide text-slate-300">LIVE EVALUATION SCORECARD</h3>
            <p className="text-xs text-slate-400">
              Evaluated {scoreStats.answeredCount} of {questions.length} procedures
            </p>
          </div>
        </div>

        <div className="flex items-center gap-6">
          <div className="text-right">
            <span className="text-xs font-semibold text-slate-400 block">SCORE PERCENT</span>
            <span className={`text-2xl font-black ${
              scoreStats.answeredCount === questions.length
                ? scoreStats.isPassed ? 'text-emerald-400' : 'text-orange-400'
                : 'text-primary'
            }`}>
              {scoreStats.percent.toFixed(1)}%
            </span>
          </div>

          <div className="h-10 w-px bg-slate-800"></div>

          <div className="text-center">
            <span className="text-xs font-semibold text-slate-400 block">STATUS</span>
            {scoreStats.answeredCount === questions.length ? (
              scoreStats.isPassed ? (
                <span className="badge badge-success font-bold py-2.5 px-3">PASS</span>
              ) : (
                <span className="badge badge-warning font-bold py-2.5 px-3">RETAKE</span>
              )
            ) : (
              <span className="badge badge-ghost border-slate-800 text-slate-400 py-2.5 px-3">INCOMPLETE</span>
            )}
          </div>
        </div>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        
        {/* Trainee & Flight Metadata Card */}
        <div className="card bg-slate-900 border border-slate-800 shadow-xl rounded-2xl p-6">
          <div className="flex items-center gap-2 text-primary mb-4 font-bold tracking-wider text-xs">
            <User className="w-4 h-4" />
            <span>FLIGHT CARD DETAILS</span>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            {/* Trainee Pilot */}
            <div className="form-control w-full">
              <label className="label py-1">
                <span className="label-text text-slate-300 font-semibold text-xs">TRAINEE NAME</span>
              </label>
              <input 
                type="text" 
                placeholder="e.g. Ayush" 
                value={metadata.trainee_name}
                onChange={e => handleMetadataChange('trainee_name', e.target.value)}
                className="input input-bordered bg-slate-950 text-white rounded-xl focus:border-primary w-full border-slate-800"
                required
              />
            </div>

            {/* Trainee Callsign */}
            <div className="form-control w-full">
              <label className="label py-1">
                <span className="label-text text-slate-300 font-semibold text-xs">TRAINEE CALLSIGN</span>
              </label>
              <input 
                type="text" 
                placeholder="e.g. QRV108" 
                value={metadata.trainee_callsign}
                onChange={e => handleMetadataChange('trainee_callsign', e.target.value.toUpperCase())}
                className="input input-bordered bg-slate-950 text-white rounded-xl focus:border-primary w-full border-slate-800"
                required
              />
            </div>

            {/* Flight Callsign */}
            <div className="form-control w-full">
              <label className="label py-1">
                <span className="label-text text-slate-300 font-semibold text-xs">FLIGHT CALLSIGN (IF)</span>
              </label>
              <input 
                type="text" 
                placeholder="e.g. QATAR 108 HEAVY" 
                value={metadata.flight_callsign}
                onChange={e => handleMetadataChange('flight_callsign', e.target.value.toUpperCase())}
                className="input input-bordered bg-slate-950 text-white rounded-xl focus:border-primary w-full border-slate-800"
                required
              />
            </div>

            {/* Flight Instructor */}
            <div className="form-control w-full">
              <label className="label py-1">
                <span className="label-text text-slate-300 font-semibold text-xs">FLIGHT INSTRUCTOR (FI)</span>
              </label>
              <input 
                type="text" 
                value={metadata.instructor_name}
                onChange={e => handleMetadataChange('instructor_name', e.target.value)}
                className="input input-bordered bg-slate-950 text-white rounded-xl focus:border-primary w-full border-slate-800"
                required
              />
            </div>

            {/* Evaluation Date */}
            <div className="form-control w-full">
              <label className="label py-1">
                <span className="label-text text-slate-300 font-semibold text-xs">EVALUATION DATE</span>
              </label>
              <div className="relative">
                <input 
                  type="date" 
                  value={metadata.flight_date}
                  onChange={e => handleMetadataChange('flight_date', e.target.value)}
                  className="input input-bordered bg-slate-950 text-white rounded-xl focus:border-primary w-full border-slate-800"
                  required
                />
              </div>
            </div>
            
          </div>
        </div>

        {/* Dynamic Procedural Checklists */}
        <div className="space-y-6">
          {questions.map((q, idx) => {
            const ans = answers[q.id] || {}
            const isAnswered = ans.selected_option_index !== null
            const isCorrect = isAnswered && !ans.is_other && (ans.selected_option_index === q.correctAnswerIndex)
            
            return (
              <div 
                key={q.id} 
                className={`card bg-slate-900 border rounded-2xl shadow-xl transition-all duration-300 ${
                  isAnswered 
                    ? isCorrect 
                      ? 'border-emerald-800 bg-slate-900/60 shadow-emerald-950/20' 
                      : 'border-orange-800 bg-slate-900/60 shadow-orange-950/20'
                    : 'border-slate-800'
                }`}
              >
                
                {/* Checklist Item Header */}
                <div className="p-6 border-b border-slate-800/60 flex justify-between items-center gap-4 flex-wrap bg-slate-950/20 rounded-t-2xl">
                  <div className="flex items-center gap-3">
                    <span className={`badge font-bold py-2.5 px-3 border ${
                      isAnswered
                        ? isCorrect 
                          ? 'badge-success border-emerald-700 bg-emerald-500/10 text-emerald-400' 
                          : 'badge-warning border-orange-700 bg-orange-500/10 text-orange-400'
                        : 'badge-ghost border-slate-800 text-slate-400 bg-slate-950/40'
                    }`}>
                      {q.id}
                    </span>
                    <h3 className="font-bold text-white tracking-wide text-sm md:text-base">
                      {q.category}
                    </h3>
                  </div>
                  
                  <span className="text-xs font-bold text-slate-500">
                    Points: {q.points || 1}
                  </span>
                </div>

                {/* Checklist Item Body */}
                <div className="p-6 space-y-6">
                  
                  {/* Evaluation Question */}
                  <div className="space-y-2">
                    <p className="text-slate-300 font-semibold text-sm md:text-base leading-relaxed">
                      {q.question}
                    </p>
                  </div>

                  {/* Options List */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {q.options.map((opt, optIdx) => {
                      const isSelected = ans.selected_option_index === optIdx && !ans.is_other
                      return (
                        <button
                          key={optIdx}
                          type="button"
                          onClick={() => handleSelectOption(q.id, optIdx, false)}
                          className={`btn p-4 h-auto min-h-12 rounded-xl text-left flex justify-start items-center transition-all duration-200 gap-3 border ${
                            isSelected 
                              ? optIdx === q.correctAnswerIndex
                                ? 'bg-emerald-500/10 border-emerald-500 text-emerald-400 hover:bg-emerald-500/20'
                                : 'bg-orange-500/10 border-orange-500 text-orange-400 hover:bg-orange-500/20'
                              : 'bg-slate-950/40 border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-white'
                          }`}
                        >
                          <div className={`w-4 h-4 rounded-full border flex-shrink-0 flex items-center justify-center ${
                            isSelected 
                              ? optIdx === q.correctAnswerIndex
                                ? 'border-emerald-400 bg-emerald-400 text-slate-950 font-bold'
                                : 'border-orange-400 bg-orange-400 text-slate-950 font-bold'
                              : 'border-slate-700'
                          }`}>
                            {isSelected && <span className="w-1.5 h-1.5 rounded-full bg-slate-950"></span>}
                          </div>
                          <span className="text-xs md:text-sm font-medium">{opt}</span>
                        </button>
                      )
                    })}

                    {/* "Others" Option */}
                    {q.allowOther && (
                      <button
                        type="button"
                        onClick={() => handleSelectOption(q.id, q.options.length, true)}
                        className={`btn p-4 h-auto min-h-12 rounded-xl text-left flex justify-start items-center transition-all duration-200 gap-3 border col-span-1 md:col-span-2 ${
                          ans.is_other
                            ? 'bg-orange-500/10 border-orange-500 text-orange-400 hover:bg-orange-500/20'
                            : 'bg-slate-950/40 border-slate-800 text-slate-400 hover:bg-slate-800 hover:text-white'
                        }`}
                      >
                        <div className={`w-4 h-4 rounded-full border flex-shrink-0 flex items-center justify-center ${
                          ans.is_other
                            ? 'border-orange-400 bg-orange-400 text-slate-950 font-bold'
                            : 'border-slate-700'
                        }`}>
                          {ans.is_other && <span className="w-1.5 h-1.5 rounded-full bg-slate-950"></span>}
                        </div>
                        <span className="text-xs md:text-sm font-medium">Others (box will open, template is marked negative)</span>
                      </button>
                    )}
                  </div>

                  {/* "Others" Comment Text Area */}
                  {ans.is_other && (
                    <div className="form-control w-full space-y-2 animate-slide-down">
                      <label className="label py-0">
                        <span className="label-text text-orange-400 font-bold text-xs">WHAT WAS OBSERVED? (REQUIRED DESCRIPTION)</span>
                      </label>
                      <textarea
                        placeholder="Describe the incorrect procedure observed during replay review..."
                        value={ans.other_text}
                        onChange={e => handleOtherTextChange(q.id, e.target.value)}
                        className="textarea textarea-bordered bg-slate-950 text-white rounded-xl focus:border-orange-500 w-full border-orange-900 p-4 h-24"
                        required
                      />
                    </div>
                  )}

                  {/* Toggle Comments Button */}
                  <div className="flex justify-between items-center pt-2">
                    <button
                      type="button"
                      onClick={() => toggleComments(q.id)}
                      className="btn btn-ghost btn-xs text-slate-500 hover:text-slate-300 gap-1 flex items-center"
                    >
                      <MessageSquare className="w-3.5 h-3.5" />
                      <span>{ans.extra_feedback ? 'Edit Feedback Comments' : '+ Add Custom Feedback Comments (Optional)'}</span>
                      {ans.showComments ? <ChevronUp className="w-3 h-3" /> : <ChevronDown className="w-3 h-3" />}
                    </button>
                  </div>

                  {/* Specific Question Comments */}
                  {ans.showComments && (
                    <div className="form-control w-full animate-slide-down">
                      <textarea
                        placeholder="Write a specific feedback comment for the trainee on this specific procedural point..."
                        value={ans.extra_feedback}
                        onChange={e => handleFeedbackChange(q.id, e.target.value)}
                        className="textarea textarea-bordered bg-slate-950 text-slate-300 rounded-xl focus:border-primary w-full border-slate-800 p-4 h-20"
                      />
                    </div>
                  )}

                  {/* Showing correct procedure feedback block on wrong answers */}
                  {isAnswered && !isCorrect && (
                    <div className="bg-amber-500/10 border border-amber-900/60 rounded-xl p-4 space-y-2">
                      <h4 className="text-xs font-bold text-amber-400 uppercase tracking-wide">⚠️ CORRECT FLIGHT OPERATION CRITERIA</h4>
                      <p className="text-xs text-amber-300/90 leading-relaxed font-mono">
                        {q.procedureDetails}
                      </p>
                    </div>
                  )}

                </div>
              </div>
            )
          })}
        </div>

        {/* General Instructor Feedback Remarks */}
        <div className="card bg-slate-900 border border-slate-800 shadow-xl rounded-2xl p-6">
          <div className="flex items-center gap-2 text-primary mb-4 font-bold tracking-wider text-xs">
            <FileText className="w-4 h-4" />
            <span>GENERAL PERFORMANCE DEBRIEF & REMARKS</span>
          </div>

          <div className="form-control w-full">
            <textarea
              placeholder="Write a detailed summary of the trainee's flight performance, highlighting areas of excellence and specific lessons to practice before the next checkride..."
              value={metadata.general_remarks}
              onChange={e => handleMetadataChange('general_remarks', e.target.value)}
              className="textarea textarea-bordered bg-slate-950 text-white rounded-xl focus:border-primary w-full border-slate-800 p-4 h-36 leading-relaxed"
              required
            />
          </div>
        </div>

        {/* Action Button */}
        <div className="pt-4">
          <button
            type="submit"
            disabled={submitting}
            className="btn btn-primary w-full h-14 rounded-2xl font-bold text-base md:text-lg flex justify-center items-center shadow-lg shadow-primary/30 gap-2 hover:scale-[1.01] active:scale-[0.99] transition-all"
          >
            {submitting ? (
              <>
                <span className="loading loading-spinner"></span>
                <span>Compiling Feedback PDF Document...</span>
              </>
            ) : (
              <>
                <Download className="w-5 h-5" />
                <span>GENERATE DETAILED FEEDBACK PDF</span>
              </>
            )}
          </button>
        </div>

      </form>
    </div>
  )
}

export default EvaluatorForm
