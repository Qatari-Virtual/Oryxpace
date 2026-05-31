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
  const [callsignNum, setCallsignNum] = useState('')
  const [searchingPilot, setSearchingPilot] = useState(false)
  const [pilotMatched, setPilotMatched] = useState(false)
  const [finalDecision, setFinalDecision] = useState('')
  
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

  // Handle callsign number input changes and reset resolved states to keep UI aligned
  const handleCallsignInputChange = (e) => {
    const val = e.target.value.replace(/\D/g, '').slice(0, 4)
    setCallsignNum(val)
    handleMetadataChange('trainee_name', '')
    handleMetadataChange('trainee_callsign', '')
    handleMetadataChange('flight_callsign', '')
    setPilotMatched(false)
  }

  // Trigger explicit database lookup for pilot details on fetch button click
  const fetchPilotDetails = () => {
    if (!callsignNum) return
    if (!/^\d+$/.test(callsignNum)) {
      alert('Please enter a valid numeric callsign.')
      return
    }

    setSearchingPilot(true)
    setPilotMatched(false)

    axios.get(`${API_BASE_URL}/api/pilots/lookup/?callsign_num=${callsignNum}`)
      .then(response => {
        if (response.data.found) {
          handleMetadataChange('trainee_name', response.data.name)
          handleMetadataChange('trainee_callsign', response.data.callsign)
          const numPart = response.data.callsign.replace('QRV', '')
          handleMetadataChange('flight_callsign', `QATAR ${numPart}`)
          setPilotMatched(true)
        } else {
          // If pilot is not found in database, populate fallback values seamlessly
          handleMetadataChange('trainee_name', 'Trainee Pilot')
          const padded = callsignNum.padStart(3, '0')
          handleMetadataChange('trainee_callsign', `QRV${padded}`)
          handleMetadataChange('flight_callsign', `QATAR ${padded}`)
          setPilotMatched(false)
          alert(`Active pilot with callsign QRV${padded} not found in database. Using default fallback details.`);
        }
        setSearchingPilot(false)
      })
      .catch(err => {
        console.error(err)
        // Set standard fallbacks on error to prevent blocking the evaluator
        handleMetadataChange('trainee_name', 'Trainee Pilot')
        const padded = callsignNum.padStart(3, '0')
        handleMetadataChange('trainee_callsign', `QRV${padded}`)
        handleMetadataChange('flight_callsign', `QATAR ${padded}`)
        setSearchingPilot(false)
        setPilotMatched(false)
      })
  }

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
      setCallsignNum('')
      setPilotMatched(false)
      setFinalDecision('')
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

  // Auto-initialize FI Final Decision to the system recommendation when all questions are answered
  useEffect(() => {
    if (questions.length === 0) return
    const unanswered = questions.filter(q => answers[q.id]?.selected_option_index === null)
    if (unanswered.length === 0) {
      const stats = calculateScore()
      // Only default it once when first completed
      if (!finalDecision) {
        setFinalDecision(stats.percent >= 75.0 ? 'PASS' : 'RETAKE')
      }
    } else {
      setFinalDecision('')
    }
  }, [answers, questions])

  const handleSubmit = (e) => {
    e.preventDefault()
    
    // Check if metadata fields are filled
    if (!metadata.trainee_name || !metadata.trainee_callsign) {
      alert('Please select a Trainee Callsign and Fetch details in the card above.')
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
      final_decision: finalDecision,
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
        <p className="text-brand-subtext font-medium animate-pulse">Fetching checklist templates from flight operations server...</p>
      </div>
    )
  }

  if (error) {
    return (
      <div className="alert alert-error bg-white border border-[#ead9df] shadow-xl rounded-2xl flex gap-4 max-w-2xl mx-auto mt-8">
        <AlertCircle className="w-8 h-8 text-error flex-shrink-0" />
        <div>
          <h3 className="font-bold text-error">System Error</h3>
          <p className="text-xs text-slate-700 mt-1">{error}</p>
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
          <h1 className="text-3xl font-bold tracking-tight text-brand-text">Trainee Flight Evaluator</h1>
          <p className="text-brand-subtext mt-1">Check replay telemetry and fill out standard procedurals for instant PDF publication.</p>
        </div>
        
        <button 
          onClick={resetForm}
          className="btn btn-sm btn-outline border-brand-border text-slate-600 hover:bg-brand-text hover:text-white hover:border-brand-text"
        >
          <RotateCcw className="w-4 h-4 mr-2" />
          Reset Checklist
        </button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        
        {/* Trainee Details Card */}
        <div className="card bg-brand-card border border-brand-border shadow-xl rounded-2xl p-6">
          <div className="flex items-center gap-2 text-primary mb-4 font-bold tracking-wider text-xs">
            <User className="w-4 h-4" />
            <span>TRAINEE DETAILS</span>
          </div>
          
          <div className="space-y-6">
            {/* Input & Fetch Button Row */}
            <div className="flex flex-col sm:flex-row gap-4 items-end max-w-xl">
              <div className="form-control w-full sm:w-2/3">
                <label className="label py-1">
                  <span className="label-text text-brand-text font-bold text-xs">TRAINEE CALLSIGN (3 DIGITS)</span>
                </label>
                <input 
                  type="text" 
                  placeholder="e.g. 108" 
                  value={callsignNum}
                  onChange={handleCallsignInputChange}
                  className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary w-full border-slate-350"
                  required
                />
              </div>
              <button
                type="button"
                onClick={fetchPilotDetails}
                disabled={searchingPilot || !callsignNum}
                className="btn btn-primary h-12 px-6 rounded-xl font-bold flex-shrink-0 flex items-center justify-center gap-2 shadow-md shadow-primary/20 hover:scale-[1.01] active:scale-[0.99] transition-all w-full sm:w-auto"
              >
                {searchingPilot ? (
                  <>
                    <span className="loading loading-spinner loading-xs"></span>
                    <span>Fetching...</span>
                  </>
                ) : (
                  <span>Fetch Details</span>
                )}
              </button>
            </div>

            {/* Read-Only Populated Info Grid */}
            {(metadata.trainee_name || metadata.trainee_callsign) && (
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-brand-border animate-fade-in">
                
                {/* Trainee Name */}
                <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60">
                  <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">Trainee Name</span>
                  <div className="flex items-center gap-2 mt-1">
                    <span className="text-sm font-bold text-slate-800">
                      {metadata.trainee_name || "Trainee Pilot"}
                    </span>
                    {pilotMatched ? (
                      <span className="text-[9px] font-bold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">DB MATCH</span>
                    ) : (
                      <span className="text-[9px] font-bold text-amber-600 bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-500/20">FALLBACK</span>
                    )}
                  </div>
                </div>

                {/* Resolved Callsign */}
                <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60">
                  <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">Resolved Callsign</span>
                  <span className="text-sm font-bold text-slate-800 mt-1 block">
                    {metadata.trainee_callsign || "—"}
                  </span>
                </div>

                {/* Evaluation Date */}
                <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60">
                  <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">Evaluation Date</span>
                  <span className="text-sm font-bold text-slate-800 mt-1 block">
                    {metadata.flight_date}
                  </span>
                </div>

              </div>
            )}

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
                className={`card bg-brand-card border rounded-2xl shadow-xl transition-all duration-300 ${
                  isAnswered 
                    ? isCorrect 
                      ? 'border-emerald-500 bg-brand-card shadow-emerald-100/10' 
                      : 'border-orange-500 bg-brand-card shadow-orange-100/10'
                    : 'border-brand-border'
                }`}
              >
                
                {/* Checklist Item Header */}
                <div className="p-6 border-b border-brand-border flex justify-between items-center gap-4 flex-wrap bg-brand-bg/50 rounded-t-2xl">
                  <div className="flex items-center gap-3">
                    <span className={`badge font-bold py-2.5 px-3 border ${
                      isAnswered
                        ? isCorrect 
                          ? 'badge-success border-emerald-700 bg-emerald-500/10 text-emerald-600' 
                          : 'badge-warning border-orange-700 bg-orange-500/10 text-orange-600'
                        : 'badge-ghost border-brand-border text-slate-500 bg-brand-bg'
                    }`}>
                      {q.id}
                    </span>
                    <h3 className="font-bold text-brand-text tracking-wide text-sm md:text-base">
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
                    <p className="text-slate-800 font-semibold text-sm md:text-base leading-relaxed">
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
                                ? 'bg-emerald-500/10 border-emerald-500 text-emerald-600 hover:bg-emerald-500/20'
                                : 'bg-orange-500/10 border-orange-500 text-orange-600 hover:bg-orange-500/20'
                              : 'bg-brand-bg border-slate-300 text-slate-600 hover:bg-brand-border/30 hover:text-brand-text'
                          }`}
                        >
                          <div className={`w-4 h-4 rounded-full border flex-shrink-0 flex items-center justify-center ${
                            isSelected 
                              ? optIdx === q.correctAnswerIndex
                                ? 'border-emerald-500 bg-emerald-500 text-slate-950 font-bold'
                                : 'border-orange-500 bg-orange-500 text-slate-950 font-bold'
                              : 'border-slate-400'
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
                            ? 'bg-orange-500/10 border-orange-500 text-orange-600 hover:bg-orange-500/20'
                            : 'bg-brand-bg border-slate-300 text-slate-600 hover:bg-brand-border/30 hover:text-brand-text'
                        }`}
                      >
                        <div className={`w-4 h-4 rounded-full border flex-shrink-0 flex items-center justify-center ${
                          ans.is_other
                            ? 'border-orange-500 bg-orange-500 text-slate-950 font-bold'
                            : 'border-slate-400'
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
                        <span className="label-text text-orange-600 font-bold text-xs">WHAT WAS OBSERVED? (REQUIRED DESCRIPTION)</span>
                      </label>
                      <textarea
                        placeholder="Describe the incorrect procedure observed during replay review..."
                        value={ans.other_text}
                        onChange={e => handleOtherTextChange(q.id, e.target.value)}
                        className="textarea textarea-bordered bg-brand-card text-slate-800 rounded-xl focus:border-orange-500 w-full border-orange-400 p-4 h-24"
                        required
                      />
                    </div>
                  )}

                  {/* Toggle Comments Button */}
                  <div className="flex justify-between items-center pt-2">
                    <button
                      type="button"
                      onClick={() => toggleComments(q.id)}
                      className="btn btn-ghost btn-xs text-slate-500 hover:text-brand-text gap-1 flex items-center font-semibold"
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
                        className="textarea textarea-bordered bg-brand-card text-slate-700 rounded-xl focus:border-primary w-full border-brand-border p-4 h-20"
                      />
                    </div>
                  )}

                  {/* Showing correct procedure feedback block on wrong answers */}
                  {isAnswered && !isCorrect && (
                    <div className="bg-[#fcf5e3] border border-accent/30 rounded-xl p-4 space-y-2">
                      <h4 className="text-xs font-bold text-accent uppercase tracking-wide">⚠️ CORRECT FLIGHT OPERATION CRITERIA</h4>
                      <p className="text-xs text-accent leading-relaxed font-mono font-semibold">
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
        <div className="card bg-brand-card border border-brand-border shadow-xl rounded-2xl p-6">
          <div className="flex items-center gap-2 text-primary mb-4 font-bold tracking-wider text-xs">
            <FileText className="w-4 h-4" />
            <span>GENERAL PERFORMANCE DEBRIEF & REMARKS</span>
          </div>

          <div className="form-control w-full">
            <textarea
              placeholder="Write a detailed summary of the trainee's flight performance, highlighting areas of excellence and specific lessons to practice before the next checkride..."
              value={metadata.general_remarks}
              onChange={e => handleMetadataChange('general_remarks', e.target.value)}
              className="textarea textarea-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary w-full border-brand-border p-4 h-36 leading-relaxed"
              required
            />
          </div>
        </div>

        {/* FI Final Decision & Summary Panel (Slides open when form is fully evaluated) */}
        {finalDecision && (
          <div className="card bg-brand-card border border-brand-border shadow-2xl rounded-2xl p-6 space-y-6 animate-slide-up">
            <div className="flex items-center gap-2 text-primary font-bold tracking-wider text-xs">
              <ClipboardCheck className="w-4 h-4 text-primary" />
              <span>CHECKRIDE SUMMARY & FINAL FI DECISION</span>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
              
              {/* Final Score Callout */}
              <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60 text-center">
                <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">Calculated Evaluation Score</span>
                <span className="text-3xl font-black text-primary mt-1 block">
                  {scoreStats.percent.toFixed(1)}%
                </span>
                <span className="text-xs text-slate-500 font-semibold block mt-1">
                  ({scoreStats.pointsEarned} / {scoreStats.totalPoints} Points)
                </span>
              </div>

              {/* System Recommendation */}
              <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60 text-center">
                <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">System Recommendation</span>
                <div className="mt-2">
                  {scoreStats.percent >= 75.0 ? (
                    <span className="badge badge-success font-bold py-2.5 px-3">PASS</span>
                  ) : (
                    <span className="badge badge-warning font-bold py-2.5 px-3">RETAKE</span>
                  )}
                </div>
                <span className="text-[10px] text-slate-500 font-medium block mt-2">
                  Rubric benchmark threshold is 75%
                </span>
              </div>

              {/* FI Final Checkride Decision Dropdown */}
              <div className="form-control w-full">
                <label className="label py-1">
                  <span className="label-text text-brand-text font-bold text-xs">FI FINAL CHECKRIDE DECISION</span>
                </label>
                <select
                  value={finalDecision}
                  onChange={e => setFinalDecision(e.target.value)}
                  className={`select select-bordered rounded-xl w-full border-slate-350 font-bold focus:border-primary ${
                    finalDecision === 'PASS' 
                      ? 'bg-emerald-500/10 text-emerald-700 border-emerald-300' 
                      : 'bg-orange-500/10 text-orange-700 border-orange-300'
                  }`}
                  required
                >
                  <option value="PASS" className="bg-white text-emerald-700 font-bold">PASS (Verify Trainee Competence)</option>
                  <option value="RETAKE" className="bg-white text-orange-700 font-bold">RETAKE (Requires Practice / Fails Rubric)</option>
                </select>
              </div>

            </div>
          </div>
        )}

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
