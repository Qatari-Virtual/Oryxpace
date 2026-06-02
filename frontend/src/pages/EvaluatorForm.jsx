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
  RotateCcw,
  Maximize2,
  Minimize2,
  X
} from 'lucide-react'

function EvaluatorForm() {
  const [questions, setQuestions] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [callsignNum, setCallsignNum] = useState('')
  const [fiCallsignNum, setFiCallsignNum] = useState('')
  const [searchingPilot, setSearchingPilot] = useState(false)
  const [pilotMatched, setPilotMatched] = useState(false)
  const [fiMatched, setFiMatched] = useState(false)
  const [finalDecision, setFinalDecision] = useState('')
  const [pdfBlobUrl, setPdfBlobUrl] = useState('')
  const [compilingPreview, setCompilingPreview] = useState(false)
  const [isFullscreenPreview, setIsFullscreenPreview] = useState(false)
  
  // Metadata fields
  const [metadata, setMetadata] = useState({
    trainee_name: '',
    trainee_callsign: '',
    instructor_name: '',
    instructor_callsign: '',
    flight_date: new Date().toISOString().split('T')[0],
    general_remarks: ''
  })

  // Trigger live ReportLab PDF compilation and stream it as a Blob URL to the iframe previewer
  const updatePdfPreview = () => {
    // Generate answers payload using the latest edited text values in our local answers state
    const payload = {
      ...metadata,
      final_decision: finalDecision,
      answers: Object.values(answers).map(ans => ({
        question_id: ans.question_id,
        selected_option_index: ans.selected_option_index,
        is_other: ans.is_other,
        other_text: ans.other_text,
        extra_feedback: ans.extra_feedback,
        category: ans.category,
        points: ans.points,
        question: ans.question,
        selected_option: ans.selected_option,
        is_correct: ans.is_correct,
        correct_statement: ans.correct_statement,
        wrong_statement: ans.wrong_statement,
        procedure_details: ans.procedure_details
      }))
    }

    setCompilingPreview(true)
    axios({
      url: `${API_BASE_URL}/api/evaluations/generate/`,
      method: 'POST',
      data: payload,
      responseType: 'blob'
    })
      .then(response => {
        if (pdfBlobUrl) {
          window.URL.revokeObjectURL(pdfBlobUrl)
        }
        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        setPdfBlobUrl(url)
        setCompilingPreview(false)
      })
      .catch(err => {
        console.error(err)
        setCompilingPreview(false)
      })
  }

  // Auto-initialize and compile the preview PDF when checkride completion or decision changes
  useEffect(() => {
    if (finalDecision && !compilingPreview) {
      const timer = setTimeout(() => {
        updatePdfPreview()
      }, 300)
      return () => clearTimeout(timer)
    }
  }, [finalDecision])

  // Selected answers state
  // key: question_id, val: { selected_option_index: null, is_other: false, other_text: '', extra_feedback: '', showComments: false }
  const [answers, setAnswers] = useState({})

  // Handle callsign number input changes and reset resolved states to keep UI aligned
  const handleCallsignInputChange = (e) => {
    const val = e.target.value.replace(/\D/g, '').slice(0, 3)
    setCallsignNum(val)
    handleMetadataChange('trainee_name', '')
    handleMetadataChange('trainee_callsign', '')
    setPilotMatched(false)
  }

  const handleFiCallsignInputChange = (e) => {
    const val = e.target.value.replace(/\D/g, '').slice(0, 3)
    setFiCallsignNum(val)
    handleMetadataChange('instructor_name', '')
    handleMetadataChange('instructor_callsign', '')
    setFiMatched(false)
  }

  // Trigger explicit database lookup for both trainee and instructor details on fetch button click
  const fetchPilotDetails = () => {
    if (!callsignNum) {
      alert('Trainee callsign is required.')
      return
    }
    if (!fiCallsignNum) {
      alert('FI callsign is required.')
      return
    }
    if (!/^\d+$/.test(callsignNum) || !/^\d+$/.test(fiCallsignNum)) {
      alert('Please enter valid 3-digit numeric callsigns.')
      return
    }

    setSearchingPilot(true)
    setPilotMatched(false)
    setFiMatched(false)

    const traineeReq = axios.get(`${API_BASE_URL}/api/pilots/lookup/?callsign_num=${callsignNum}`)
    const fiReq = axios.get(`${API_BASE_URL}/api/pilots/lookup/?callsign_num=${fiCallsignNum}`)

    Promise.all([traineeReq, fiReq])
      .then(([traineeRes, fiRes]) => {
        // 1. Process Trainee Result
        if (traineeRes.data.found) {
          handleMetadataChange('trainee_name', traineeRes.data.name)
          handleMetadataChange('trainee_callsign', traineeRes.data.callsign)
          setPilotMatched(true)
        } else {
          handleMetadataChange('trainee_name', 'Trainee Pilot')
          const padded = callsignNum.padStart(3, '0')
          handleMetadataChange('trainee_callsign', `QRV${padded}`)
          setPilotMatched(false)
        }

        // 2. Process FI Result
        if (fiRes.data.found) {
          handleMetadataChange('instructor_name', fiRes.data.name)
          handleMetadataChange('instructor_callsign', fiRes.data.callsign)
          setFiMatched(true)
        } else {
          handleMetadataChange('instructor_name', 'Flight Instructor')
          const padded = fiCallsignNum.padStart(3, '0')
          handleMetadataChange('instructor_callsign', `QRV${padded}`)
          setFiMatched(false)
        }

        const traineeNotFound = !traineeRes.data.found
        const fiNotFound = !fiRes.data.found
        if (traineeNotFound || fiNotFound) {
          let warnMsg = "Lookups completed with warnings:\n"
          if (traineeNotFound) {
            warnMsg += `- Active trainee pilot with callsign QRV${callsignNum.padStart(3, '0')} not found.\n`
          }
          if (fiNotFound) {
            warnMsg += `- Active flight instructor with callsign QRV${fiCallsignNum.padStart(3, '0')} not found.\n`
          }
          warnMsg += "Using fallback details."
          alert(warnMsg)
        }
        setSearchingPilot(false)
      })
      .catch(err => {
        console.error(err)
        // Fallback trainee
        handleMetadataChange('trainee_name', 'Trainee Pilot')
        const paddedTrainee = callsignNum.padStart(3, '0')
        handleMetadataChange('trainee_callsign', `QRV${paddedTrainee}`)
        setPilotMatched(false)

        // Fallback instructor
        handleMetadataChange('instructor_name', 'Flight Instructor')
        const paddedFi = fiCallsignNum.padStart(3, '0')
        handleMetadataChange('instructor_callsign', `QRV${paddedFi}`)
        setFiMatched(false)

        setSearchingPilot(false)
      })
  }

  // Fetch rubrics
  useEffect(() => {
    axios.get(`${API_BASE_URL}/api/evaluations/questions/`)
      .then(response => {
        setQuestions(response.data)
        
        // Initialize answers state with editable templates
        const initialAnswers = {}
        response.data.forEach(q => {
          initialAnswers[q.id] = {
            question_id: q.id,
            selected_option_index: null,
            is_other: false,
            other_text: '',
            extra_feedback: '',
            showComments: false,
            
            // Editable copies of template values
            category: q.category,
            question: q.question,
            points: q.points || 1,
            correct_statement: q.correctStatement,
            wrong_statement: q.wrongStatement,
            procedure_details: q.procedureDetails,
            selected_option: '',
            options: [...q.options],
            is_correct: null
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

  // Calculate live score using user-customized points and correctness states
  const calculateScore = () => {
    let totalPoints = 0
    let pointsEarned = 0
    let answeredCount = 0

    questions.forEach(q => {
      const ans = answers[q.id]
      if (ans) {
        const pts = Number(ans.points) || 0
        totalPoints += pts
        if (ans.selected_option_index !== null) {
          answeredCount++
          const isCorrect = !ans.is_other && (ans.selected_option_index === q.correctAnswerIndex)
          if (isCorrect) {
            pointsEarned += pts
          }
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
    setAnswers(prev => {
      const ans = prev[qId]
      let selectedOptionText = ""
      
      if (isOther) {
        selectedOptionText = "Others"
      } else if (optionIdx !== null && optionIdx >= 0 && optionIdx < ans.options.length) {
        selectedOptionText = ans.options[optionIdx]
      }
      
      const isCorrect = isOther ? false : (optionIdx === questions.find(q => q.id === qId).correctAnswerIndex)
      
      return {
        ...prev,
        [qId]: {
          ...ans,
          selected_option_index: optionIdx,
          is_other: isOther,
          selected_option: selectedOptionText,
          is_correct: isCorrect,
          other_text: isOther ? ans.other_text : ''
        }
      }
    })
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

  // Set any general text block dynamically within a specific checklist item
  const handleAnswerTextChange = (qId, field, val) => {
    setAnswers(prev => ({
      ...prev,
      [qId]: {
        ...prev[qId],
        [field]: val
      }
    }))
  }

  // Set custom options text dynamically and sync with selection
  const handleOptionTextChange = (qId, optIdx, val) => {
    setAnswers(prev => {
      const ans = prev[qId]
      const newOptions = [...ans.options]
      newOptions[optIdx] = val
      
      const updatedAns = {
        ...ans,
        options: newOptions
      }
      
      if (!updatedAns.is_other && updatedAns.selected_option_index === optIdx) {
        updatedAns.selected_option = val
      }
      
      return {
        ...prev,
        [qId]: updatedAns
      }
    })
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
      setFiCallsignNum('')
      setPilotMatched(false)
      setFiMatched(false)
      setFinalDecision('')
      if (pdfBlobUrl) {
        window.URL.revokeObjectURL(pdfBlobUrl)
        setPdfBlobUrl('')
      }
      setMetadata({
        trainee_name: '',
        trainee_callsign: '',
        instructor_name: '',
        instructor_callsign: '',
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
      if (pdfBlobUrl) {
        window.URL.revokeObjectURL(pdfBlobUrl)
        setPdfBlobUrl('')
      }
    }
  }, [answers, questions])

  const handleSubmit = (e) => {
    e.preventDefault()
    
    // Check if metadata fields are filled
    if (!metadata.trainee_name || !metadata.trainee_callsign || !metadata.instructor_name || !metadata.instructor_callsign) {
      alert('Please fill in both Callsigns and click Fetch Details in the card above.')
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
        extra_feedback: ans.extra_feedback,
        category: ans.category,
        points: ans.points,
        question: ans.question,
        selected_option: ans.selected_option,
        is_correct: ans.is_correct,
        correct_statement: ans.correct_statement,
        wrong_statement: ans.wrong_statement,
        procedure_details: ans.procedure_details
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
        
        {/* Trainee & Instructor Details Card */}
        <div className="card bg-brand-card border border-brand-border shadow-xl rounded-2xl p-6">
          <div className="flex items-center gap-2 text-primary mb-4 font-bold tracking-wider text-xs">
            <User className="w-4 h-4" />
            <span>TRAINEE & INSTRUCTOR DETAILS</span>
          </div>
          
          <div className="space-y-6">
            {/* Input & Fetch Button Row */}
            <div className="flex flex-col sm:flex-row gap-4 items-end">
              <div className="form-control w-full sm:w-1/3">
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

              <div className="form-control w-full sm:w-1/3">
                <label className="label py-1">
                  <span className="label-text text-brand-text font-bold text-xs">FI CALLSIGN (3 DIGITS)</span>
                </label>
                <input 
                  type="text" 
                  placeholder="e.g. 095" 
                  value={fiCallsignNum}
                  onChange={handleFiCallsignInputChange}
                  className="input input-bordered bg-brand-card text-slate-800 rounded-xl focus:border-primary w-full border-slate-350"
                  required
                />
              </div>

              <button
                type="button"
                onClick={fetchPilotDetails}
                disabled={searchingPilot || !callsignNum || !fiCallsignNum}
                className="btn btn-primary h-12 px-6 rounded-xl font-bold flex-shrink-0 flex items-center justify-center gap-2 shadow-md shadow-primary/20 hover:scale-[1.01] active:scale-[0.99] transition-all w-full sm:w-auto"
              >
                {searchingPilot ? (
                  <>
                    <span className="loading loading-spinner loading-xs"></span>
                    <span>Resolving Crew...</span>
                  </>
                ) : (
                  <span>Fetch Details</span>
                )}
              </button>
            </div>

            {/* Read-Only Populated Info Grid */}
            {(metadata.trainee_name || metadata.trainee_callsign || metadata.instructor_name || metadata.instructor_callsign) && (
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-brand-border animate-fade-in">
                
                {/* Trainee Name */}
                <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60">
                  <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">Trainee Pilot</span>
                  <div className="flex items-center gap-2 mt-1 flex-wrap">
                    <span className="text-sm font-bold text-slate-800">
                      {metadata.trainee_name || "Trainee Pilot"}
                    </span>
                    {metadata.trainee_callsign && (
                      <span className="text-xs font-mono text-primary font-bold">
                        ({metadata.trainee_callsign})
                      </span>
                    )}
                    {pilotMatched ? (
                      <span className="text-[8px] font-bold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">DB MATCH</span>
                    ) : metadata.trainee_callsign ? (
                      <span className="text-[8px] font-bold text-amber-600 bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-500/20">FALLBACK</span>
                    ) : null}
                  </div>
                </div>

                {/* Instructor Details */}
                <div className="bg-brand-bg p-4 rounded-xl border border-brand-border/60">
                  <span className="text-[10px] font-bold text-brand-subtext uppercase block tracking-wider">Flight Instructor (FI)</span>
                  <div className="flex items-center gap-2 mt-1 flex-wrap">
                    <span className="text-sm font-bold text-slate-800">
                      {metadata.instructor_name || "Flight Instructor"}
                    </span>
                    {metadata.instructor_callsign && (
                      <span className="text-xs font-mono text-primary font-bold">
                        ({metadata.instructor_callsign})
                      </span>
                    )}
                    {fiMatched ? (
                      <span className="text-[8px] font-bold text-emerald-600 bg-emerald-500/10 px-1.5 py-0.5 rounded border border-emerald-500/20">DB MATCH</span>
                    ) : metadata.instructor_callsign ? (
                      <span className="text-[8px] font-bold text-amber-600 bg-amber-500/10 px-1.5 py-0.5 rounded border border-amber-500/20">FALLBACK</span>
                    ) : null}
                  </div>
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
        </div>        {/* Dynamic Procedural Checklists */}
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
                  <div className="flex items-center gap-3 w-full sm:w-auto">
                    <span className={`badge font-bold py-2.5 px-3 border flex-shrink-0 ${
                      isAnswered
                        ? isCorrect 
                          ? 'badge-success border-emerald-700 bg-emerald-500/10 text-emerald-600' 
                          : 'badge-warning border-orange-700 bg-orange-500/10 text-orange-600'
                        : 'badge-ghost border-brand-border text-slate-500 bg-brand-bg'
                    }`}>
                      {q.id}
                    </span>
                    
                    {/* Category Title */}
                    <div className="flex items-center gap-1.5 w-full">
                      <span className="font-bold text-brand-text tracking-wide text-sm md:text-base font-sans">
                        {q.category}
                      </span>
                    </div>
                  </div>
                  
                  {/* Points Weight */}
                  <div className="flex items-center gap-1 text-xs font-bold text-slate-500">
                    <span>Points: {q.points || 1}</span>
                  </div>
                </div>

                {/* Checklist Item Body */}
                <div className="p-6 space-y-6">
                  
                  {/* Evaluation Question */}
                  <div className="bg-slate-50/50 p-4 rounded-xl border border-brand-border/60">
                    <p className="text-slate-800 font-semibold text-sm md:text-base leading-relaxed">
                      {q.question}
                    </p>
                  </div>

                  {/* Options List */}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {(ans.options || []).map((opt, optIdx) => {
                      const isSelected = ans.selected_option_index === optIdx && !ans.is_other
                      return (
                        <div
                          key={optIdx}
                          className={`flex items-center p-3 px-4 rounded-xl transition-all duration-200 gap-3 border ${
                            isSelected 
                              ? optIdx === q.correctAnswerIndex
                                ? 'bg-emerald-500/10 border-emerald-500 text-emerald-600'
                                : 'bg-orange-500/10 border-orange-500 text-orange-600'
                              : 'bg-brand-bg border-slate-300 text-slate-600 hover:bg-brand-border/30'
                          }`}
                        >
                          <button
                            type="button"
                            onClick={() => handleSelectOption(q.id, optIdx, false)}
                            className="flex-shrink-0 flex items-center justify-center cursor-pointer"
                          >
                            <div className={`w-4 h-4 rounded-full border flex items-center justify-center ${
                              isSelected 
                                ? optIdx === q.correctAnswerIndex
                                  ? 'border-emerald-500 bg-emerald-500 text-slate-950 font-bold'
                                  : 'border-orange-500 bg-orange-500 text-slate-950 font-bold'
                                : 'border-slate-400'
                            }`}>
                              {isSelected && <span className="w-1.5 h-1.5 rounded-full bg-slate-950"></span>}
                            </div>
                          </button>
                          
                          {/* Option text */}
                          <span className="font-medium text-xs md:text-sm text-slate-700">
                            {opt}
                          </span>
                        </div>
                      )
                    })}

                    {/* "Others" Option */}
                    {q.allowOther && (
                      <button
                        type="button"
                        onClick={() => handleSelectOption(q.id, ans.options.length, true)}
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
                  )}                  {/* Showing correct procedure feedback block on wrong answers */}
                  {isAnswered && !isCorrect && (
                    <div className="bg-[#fcf5e3] border border-accent/30 rounded-xl p-4 space-y-2">
                      <h4 className="text-xs font-bold text-accent uppercase tracking-wide">Correct Flight Operation Criteria</h4>
                      
                      <div className="space-y-2 text-xs">
                        <p className="text-orange-700 font-bold flex items-center gap-1.5">
                          <span className="select-none">⚠️ Common Mistake:</span>
                          <span>{q.wrongStatement}</span>
                        </p>
                        <div className="text-slate-700 leading-relaxed">
                          <span className="font-bold text-slate-500 block text-[10px] uppercase">Correct Technique/Procedure Guide:</span>
                          <span className="block mt-0.5">{q.procedureDetails}</span>
                        </div>
                      </div>
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
          <div className="space-y-6 animate-slide-up">
            
            {/* Decision Controls Card */}
            <div className="card bg-brand-card border border-brand-border shadow-xl rounded-2xl p-6 space-y-6">
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

            {/* Split-Screen Live PDF Preview Dashboard */}
            <div className="space-y-4">
              <span className="text-xs font-bold text-slate-500 block uppercase tracking-widest pl-1">
                📄 Live PDF Preview & Remarks Editor Dashboard
              </span>
              
              <div className="border border-brand-border shadow-2xl rounded-3xl bg-[#f8fafc] p-6">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                  
                  {/* Left Column: Remarks & Comments Editor (span 5) */}
                  <div className="lg:col-span-5 flex flex-col space-y-6">
                    
                    {/* Overall Remarks Editor */}
                    <div className="space-y-2">
                      <label className="text-xs font-bold uppercase text-slate-700 tracking-wider block">
                        1. Edit Overall Debrief Remarks
                      </label>
                      <div className="bg-white border border-brand-border rounded-2xl p-4 shadow-inner">
                        <textarea
                          value={metadata.general_remarks}
                          onChange={e => handleMetadataChange('general_remarks', e.target.value)}
                          placeholder="Write general checkride performance feedback here..."
                          className="w-full bg-transparent border-0 focus:ring-0 text-slate-700 leading-relaxed font-sans text-xs outline-none focus:outline-none p-0 resize-none h-24"
                          required
                        />
                      </div>
                    </div>

                    {/* Specific Comments Quick-Edit */}
                    <div className="space-y-2 flex-grow flex flex-col">
                      <label className="text-xs font-bold uppercase text-slate-700 tracking-wider block">
                        2. Review & Edit Checklist Item Remarks
                      </label>
                      <div className="border border-slate-200 bg-white rounded-2xl p-4 shadow-inner max-h-[300px] overflow-y-auto space-y-3 pr-2 flex-grow">
                        {questions.map(q => {
                          const ans = answers[q.id] || {}
                          const isAnswered = ans.selected_option_index !== null
                          const isCorrect = isAnswered && !ans.is_other && (ans.selected_option_index === q.correctAnswerIndex)
                          
                          let statusBadge = null
                          if (isAnswered) {
                            statusBadge = isCorrect ? (
                              <span className="text-[9px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">PASS</span>
                            ) : (
                              <span className="text-[9px] font-bold text-orange-600 bg-orange-50 px-1.5 py-0.5 rounded border border-orange-200">RETAKE</span>
                            )
                          }
                          
                          return (
                            <div key={q.id} className="p-3 border border-slate-100 rounded-xl bg-slate-50/50 hover:bg-slate-50 transition-colors space-y-2">
                              <div className="flex justify-between items-center gap-2">
                                <span className="text-xs font-bold text-slate-800">{q.id}: {ans.category || q.category}</span>
                                {statusBadge}
                              </div>
                              <p className="text-[10px] text-slate-500 italic line-clamp-1">Q: {ans.question || q.question}</p>
                              <div className="flex items-center gap-1.5">
                                <span className="text-xs">✏️</span>
                                <input 
                                  type="text" 
                                  placeholder="Write specific feedback comments..." 
                                  value={ans.extra_feedback || ""}
                                  onChange={e => handleFeedbackChange(q.id, e.target.value)}
                                  className="w-full bg-white border border-slate-200 focus:border-primary rounded px-2.5 py-1 text-xs outline-none shadow-sm text-slate-700"
                                />
                              </div>
                            </div>
                          )
                        })}
                      </div>
                    </div>

                    {/* Update Preview Trigger Box */}
                    <div className="pt-2">
                      <button
                        type="button"
                        onClick={updatePdfPreview}
                        disabled={compilingPreview}
                        className="btn bg-[#8B1538] hover:bg-[#70102c] active:scale-[0.98] border-0 text-white w-full py-3 h-auto rounded-xl font-bold transition-all shadow-md flex items-center justify-center gap-2"
                      >
                        {compilingPreview ? (
                          <>
                            <span className="loading loading-spinner loading-xs"></span>
                            <span>Compiling Live Preview...</span>
                          </>
                        ) : (
                          <>
                            <span>🔄 Update Live PDF Preview</span>
                          </>
                        )}
                      </button>
                      <span className="text-[10px] text-slate-400 text-center block mt-1">
                        Updates the compiled vector PDF preview canvas.
                      </span>
                    </div>

                  </div>
                  
                  {/* Right Column: Live PDF Preview Canvas (span 7) */}
                  <div className="lg:col-span-7 flex flex-col space-y-3 h-[600px]">
                    <div className="flex justify-between items-center text-xs font-bold uppercase text-slate-500 px-1">
                      <div className="flex items-center gap-2">
                        <span>Live PDF Preview Canvas</span>
                        {pdfBlobUrl && (
                          <button
                            type="button"
                            onClick={() => setIsFullscreenPreview(true)}
                            className="btn btn-ghost btn-xs text-slate-400 hover:text-slate-700 hover:bg-slate-200 p-1 h-auto min-h-0 rounded"
                            title="Open Full View Preview"
                          >
                            <Maximize2 className="w-3.5 h-3.5" />
                          </button>
                        )}
                      </div>
                      {compilingPreview ? (
                        <span className="text-[10px] text-[#8B1538] animate-pulse">Compiling vector elements...</span>
                      ) : (
                        <span className="text-[10px] text-emerald-600">100% Visual Fidelity</span>
                      )}
                    </div>
                    
                    <div className="relative border border-slate-250 bg-slate-100 shadow-2xl rounded-2xl overflow-hidden flex-grow flex items-center justify-center">
                      {compilingPreview && (
                        <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex flex-col items-center justify-center text-white z-10 space-y-3">
                          <span className="loading loading-spinner loading-lg text-white"></span>
                          <p className="font-bold text-sm tracking-wide animate-pulse">Compiling ReportLab PDF Preview...</p>
                          <p className="text-xs text-slate-300">Rendering high-fidelity page layouts, grids, and stamps</p>
                        </div>
                      )}
                      
                      {pdfBlobUrl ? (
                        <iframe 
                          src={pdfBlobUrl} 
                          className="w-full h-full border-0 bg-white"
                          title="Live PDF Evaluation Preview"
                        />
                      ) : (
                        <div className="flex flex-col items-center justify-center text-center p-8 space-y-4">
                          <span className="loading loading-spinner loading-md text-primary"></span>
                          <p className="text-sm font-semibold text-slate-500">Preparing draft preview...</p>
                        </div>
                      )}
                    </div>
                  </div>

                </div>
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

          </div>
        )}

        {/* Fullscreen PDF Preview Modal */}
        {isFullscreenPreview && pdfBlobUrl && (
          <div className="fixed inset-0 z-[100] bg-slate-900/60 backdrop-blur-xs flex flex-col p-4 md:p-8 animate-fade-in">
            <div className="bg-white rounded-3xl shadow-2xl flex-grow flex flex-col w-full max-w-6xl mx-auto overflow-hidden border border-slate-200">
              {/* Modal Header */}
              <div className="bg-slate-50 px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <div className="flex items-center gap-2">
                  <span className="badge badge-primary py-2 px-2.5 bg-[#8B1538] border-0 text-white font-bold text-xs uppercase">Live Report</span>
                  <h3 className="font-extrabold text-brand-text text-sm md:text-base">Full-Screen Flight Evaluation Preview</h3>
                </div>
                <button
                  type="button"
                  onClick={() => setIsFullscreenPreview(false)}
                  className="btn btn-circle btn-sm btn-ghost text-slate-500 hover:text-slate-950 hover:bg-slate-200 flex items-center justify-center"
                  title="Close Full View"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>
              
              {/* Modal PDF Canvas */}
              <div className="flex-grow bg-slate-100 relative">
                {compilingPreview && (
                  <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex flex-col items-center justify-center text-white z-10 space-y-3">
                    <span className="loading loading-spinner loading-lg text-white"></span>
                    <p className="font-bold text-sm tracking-wide animate-pulse">Compiling ReportLab PDF Preview...</p>
                  </div>
                )}
                <iframe 
                  src={pdfBlobUrl} 
                  className="w-full h-full border-0 bg-white"
                  title="Fullscreen Live PDF Evaluation Preview"
                />
              </div>
              
              {/* Modal Footer */}
              <div className="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-between items-center flex-wrap gap-4 text-xs font-bold text-slate-500">
                <span>Oryxpace High-Fidelity Flight Summary Sheet</span>
                <button
                  type="button"
                  onClick={() => setIsFullscreenPreview(false)}
                  className="btn btn-sm btn-outline border-slate-350 text-slate-600 rounded-lg"
                >
                  Close Preview
                </button>
              </div>
            </div>
          </div>
        )}

      </form>
    </div>
  )
}

export default EvaluatorForm
