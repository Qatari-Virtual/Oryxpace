import io
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    """
    Two-pass canvas to dynamically compute and render 'Page X of Y' footers.
    """
    def __init__(self, *args, **kwargs):
        super(NumberedCanvas, self).__init__(*args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        for state in self._saved_page_states:
            self.__dict__.update(state)
            self.draw_page_decorations(num_pages)
            super(NumberedCanvas, self).showPage()
        super(NumberedCanvas, self).save()

    def draw_page_decorations(self, page_count):
        self.saveState()
        self.setFont("Helvetica", 9)
        self.setFillColor(colors.HexColor("#64748b")) # slate-500
        
        # Footer text
        footer_text = f"Page {self._pageNumber} of {page_count}"
        self.drawRightString(letter[0] - 54, 36, footer_text)
        
        system_text = "Oryxpace - Infinite Flight Simulator Evaluation System"
        self.drawString(54, 36, system_text)
        
        # Header thin rule & category title (only on pages > 1)
        if self._pageNumber > 1:
            self.setStrokeColor(colors.HexColor("#e2e8f0")) # slate-200
            self.setLineWidth(0.5)
            self.line(54, letter[1] - 40, letter[0] - 54, letter[1] - 40)
            self.drawString(54, letter[1] - 34, "Trainee Performance Feedback Report")
            
        self.restoreState()

def generate_evaluation_pdf(data):
    """
    Generates a high-fidelity PDF report of the flight simulator evaluation.
    data format:
    {
        "trainee_name": "Ayush",
        "trainee_callsign": "QRV108",
        "instructor_name": "Bumy",
        "flight_date": "2026-05-30",
        "flight_callsign": "QATAR 108",
        "general_remarks": "Overall good flight, but needs work on ground taxi speeds and procedure sequencing...",
        "answers": [
            {
                "id": "FP2",
                "question": "When were both engines started?",
                "category": "Engine Start Procedure",
                "selected_option": "Both started together",
                "is_correct": False,
                "is_other": False,
                "other_text": "",
                "extra_feedback": "Felt a bit rushed here. Take your time.",
                "correct_statement": "Correct Engine starting procedure ✅",
                "wrong_statement": "Both engines should start one by one not together.",
                "procedure_details": "First Start Engine 1, wait for N1 to become 20%...",
                "points": 1
            },
            ...
        ]
    }
    """
    buffer = io.BytesIO()
    
    # Page setup
    # Margins: 0.75 in (54 pt)
    margin = 54
    doc = SimpleDocTemplate(
        buffer,
        pagesize=letter,
        leftMargin=margin,
        rightMargin=margin,
        topMargin=margin,
        bottomMargin=margin + 18
    )
    
    story = []
    
    # Custom styles
    styles = getSampleStyleSheet()
    
    # Set up distinct palette
    primary_color = colors.HexColor("#0f172a")    # Slate-900 (Deep Charcoal)
    secondary_color = colors.HexColor("#0284c7")  # Sky-600 (Aero Blue)
    success_color = colors.HexColor("#10b981")    # Emerald-500
    danger_color = colors.HexColor("#f97316")     # Orange-500 (Safety Alert)
    gold_color = colors.HexColor("#b45309")       # Amber-700
    gold_bg_color = colors.HexColor("#fef3c7")    # Amber-100
    light_bg = colors.HexColor("#f8fafc")         # Slate-50
    border_color = colors.HexColor("#cbd5e1")     # Slate-300
    
    # Register/Update Styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=24,
        leading=28,
        textColor=primary_color,
        spaceAfter=6
    )
    
    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=12,
        leading=16,
        textColor=colors.HexColor("#475569"),
        spaceAfter=15
    )
    
    section_title_style = ParagraphStyle(
        'SectionTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=14,
        leading=18,
        textColor=primary_color,
        spaceBefore=15,
        spaceAfter=8,
        keepWithNext=True
    )
    
    body_style = ParagraphStyle(
        'Body',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=14,
        textColor=colors.HexColor("#1e293b")
    )
    
    meta_label_style = ParagraphStyle(
        'MetaLabel',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9,
        leading=12,
        textColor=colors.HexColor("#64748b")
    )
    
    meta_val_style = ParagraphStyle(
        'MetaVal',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=13,
        textColor=colors.HexColor("#0f172a")
    )
    
    score_label_style = ParagraphStyle(
        'ScoreLabel',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=11,
        leading=14,
        textColor=primary_color,
        alignment=TA_CENTER
    )
    
    score_val_style = ParagraphStyle(
        'ScoreVal',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=28,
        leading=32,
        textColor=secondary_color,
        alignment=TA_CENTER
    )
    
    status_pass_style = ParagraphStyle(
        'StatusPass',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=15,
        textColor=success_color,
        alignment=TA_CENTER
    )
    
    status_fail_style = ParagraphStyle(
        'StatusFail',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=15,
        textColor=danger_color,
        alignment=TA_CENTER
    )
    
    # Math calculation
    total_score = sum(item.get("points", 1) for item in data["answers"])
    score_earned = sum(item.get("points", 1) for item in data["answers"] if item.get("is_correct", False))
    grade_percentage = (score_earned / total_score * 100) if total_score > 0 else 0
    passed = grade_percentage >= 75.0
    
    # Document Header
    story.append(Paragraph("FLIGHT EVALUATION REPORT", title_style))
    story.append(Paragraph("Infinite Flight Simulator Trainee Performance Checklist", subtitle_style))
    
    # 2-Column Metadata & Score Block
    # Widths total 504 pt (Letter width 612 - margins 108)
    meta_col_width = 354
    score_col_width = 150
    
    # Metadata sub-table
    meta_data = [
        [Paragraph("TRAINEE PILOT", meta_label_style), Paragraph("FLIGHT INSTRUCTOR (FI)", meta_label_style)],
        [Paragraph(f"<b>{data['trainee_name']}</b> ({data['trainee_callsign']})", meta_val_style), Paragraph(data['instructor_name'], meta_val_style)],
        [Spacer(1, 4), Spacer(1, 4)],
        [Paragraph("FLIGHT CALLSIGN", meta_label_style), Paragraph("EVALUATION DATE", meta_label_style)],
        [Paragraph(data['flight_callsign'], meta_val_style), Paragraph(data['flight_date'], meta_val_style)]
    ]
    meta_sub_table = Table(meta_data, colWidths=[177, 177])
    meta_sub_table.setStyle(TableStyle([
        ('ALIGN', (0,0), (-1,-1), 'LEFT'),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 1),
        ('TOPPADDING', (0,0), (-1,-1), 1),
    ]))
    
    # Scorecard Panel
    status_paragraph = Paragraph("PASS", status_pass_style) if passed else Paragraph("RETAKE", status_fail_style)
    score_data = [
        [Paragraph("FINAL GRADE", score_label_style)],
        [Paragraph(f"{grade_percentage:.1f}%", score_val_style)],
        [Paragraph(f"{score_earned} / {total_score} Points", score_label_style)],
        [status_paragraph]
    ]
    score_sub_table = Table(score_data, colWidths=[score_col_width])
    score_sub_table.setStyle(TableStyle([
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
    ]))
    
    # Main outer table matching metadata and scorecard
    main_header_table = Table([[meta_sub_table, score_sub_table]], colWidths=[meta_col_width, score_col_width])
    main_header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (0,0), (-1,-1), 'LEFT'),
        # Styling the Score Box
        ('BACKGROUND', (1,0), (1,0), colors.HexColor("#f1f5f9")),
        ('BOX', (1,0), (1,0), 1, colors.HexColor("#cbd5e1")),
        ('LEFTPADDING', (1,0), (1,0), 8),
        ('RIGHTPADDING', (1,0), (1,0), 8),
        ('TOPPADDING', (1,0), (1,0), 10),
        ('BOTTOMPADDING', (1,0), (1,0), 10),
        # Spacing
        ('RIGHTPADDING', (0,0), (0,0), 12),
    ]))
    
    story.append(main_header_table)
    story.append(Spacer(1, 15))
    
    # Overall Feedback Remarks
    if data.get("general_remarks"):
        remarks_block = []
        remarks_block.append(Paragraph("INSTRUCTOR OVERALL REMARKS & DEBRIEF", section_title_style))
        remarks_content = Paragraph(data["general_remarks"].replace("\n", "<br/>"), body_style)
        
        remarks_table = Table([[remarks_content]], colWidths=[504])
        remarks_table.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (0,0), colors.HexColor("#f8fafc")),
            ('BOX', (0,0), (0,0), 1, border_color),
            ('LINELEFT', (0,0), (0,0), 3.5, secondary_color), # sky-600 thick left accent line
            ('TOPPADDING', (0,0), (0,0), 10),
            ('BOTTOMPADDING', (0,0), (0,0), 10),
            ('LEFTPADDING', (0,0), (0,0), 12),
            ('RIGHTPADDING', (0,0), (0,0), 12),
        ]))
        remarks_block.append(remarks_table)
        story.append(KeepTogether(remarks_block))
        story.append(Spacer(1, 15))
    
    # Step-by-Step Procedure Checklist Evaluation
    story.append(Paragraph("DETAILED PERFORMANCE EVALUATION CHECKLIST", section_title_style))
    
    # Loop over categories/answers
    for idx, ans in enumerate(data["answers"]):
        question_block = []
        
        # Header for the question item
        item_status_color = success_color if ans["is_correct"] else danger_color
        item_status_text = "CORRECT" if ans["is_correct"] else "INCORRECT"
        if ans.get("is_other", False):
            item_status_text = "INCORRECT (OTHER)"
            
        header_text = f"<b>{ans['id']}: {ans['category']}</b>"
        points_text = f"Points: {ans.get('points', 1)} / {ans.get('points', 1) if ans['is_correct'] else 0}"
        
        # Item Header Layout
        header_p = Paragraph(header_text, ParagraphStyle('ItemH', parent=body_style, fontName='Helvetica-Bold', fontSize=10, leading=13))
        points_p = Paragraph(points_text, ParagraphStyle('ItemP', parent=body_style, alignment=TA_RIGHT, fontSize=9, fontName='Helvetica-Bold', textColor=colors.HexColor("#475569")))
        
        item_header_table = Table([[header_p, points_p]], colWidths=[400, 104])
        item_header_table.setStyle(TableStyle([
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('BOTTOMPADDING', (0,0), (-1,-1), 4),
            ('TOPPADDING', (0,0), (-1,-1), 4),
            ('LINEBELOW', (0,0), (-1,-1), 0.5, border_color)
        ]))
        question_block.append(item_header_table)
        question_block.append(Spacer(1, 6))
        
        # Question Statement
        question_p = Paragraph(f"<b>Question:</b> {ans['question']}", body_style)
        question_block.append(question_p)
        question_block.append(Spacer(1, 4))
        
        # Answer selected
        selected_text = ans['selected_option']
        if ans.get("is_other", False) and ans.get("other_text"):
            selected_text = f"Other: {ans['other_text']}"
            
        answer_prefix = "<b>Trainee Action:</b> "
        answer_p = Paragraph(f"{answer_prefix}<font color='{item_status_color.hexval()}'><b>{selected_text}</b> ({item_status_text})</font>", body_style)
        question_block.append(answer_p)
        question_block.append(Spacer(1, 6))
        
        # Evaluation Result Statements
        if ans["is_correct"]:
            statement_style = ParagraphStyle('StatS', parent=body_style, textColor=success_color, fontName='Helvetica-Bold')
            statement_p = Paragraph(ans["correct_statement"], statement_style)
            question_block.append(statement_p)
        else:
            # Common mistake warning
            mistake_style = ParagraphStyle('StatM', parent=body_style, textColor=colors.HexColor("#c2410c"), fontName='Helvetica-Bold')
            mistake_p = Paragraph(f"⚠️ Common Mistake: {ans['wrong_statement']}", mistake_style)
            question_block.append(mistake_p)
            question_block.append(Spacer(1, 6))
            
            # Correct Procedure Block
            procedure_p = Paragraph(f"<b>Correct Procedure:</b> {ans['procedure_details']}", ParagraphStyle('StatP', parent=body_style, fontSize=9.5, leading=13.5, textColor=gold_color))
            
            procedure_table = Table([[procedure_p]], colWidths=[480])
            procedure_table.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), gold_bg_color),
                ('BOX', (0,0), (0,0), 0.5, colors.HexColor("#f59e0b")),
                ('TOPPADDING', (0,0), (0,0), 6),
                ('BOTTOMPADDING', (0,0), (0,0), 6),
                ('LEFTPADDING', (0,0), (0,0), 10),
                ('RIGHTPADDING', (0,0), (0,0), 10),
            ]))
            question_block.append(procedure_table)
            
        # Extra Feedback
        if ans.get("extra_feedback"):
            question_block.append(Spacer(1, 6))
            feedback_p = Paragraph(f"<i>FI Custom Feedback: \"{ans['extra_feedback']}\"</i>", ParagraphStyle('StatF', parent=body_style, textColor=colors.HexColor("#475569"), fontName='Helvetica-Oblique'))
            question_block.append(feedback_p)
            
        # Outer Card Wrapper for the entire Checklist Item
        outer_card_table = Table([[question_block]], colWidths=[504])
        outer_card_table.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (0,0), light_bg),
            ('BOX', (0,0), (0,0), 1, border_color),
            ('LINELEFT', (0,0), (0,0), 4, item_status_color), # left accent status colored bar (green or orange)
            ('TOPPADDING', (0,0), (0,0), 10),
            ('BOTTOMPADDING', (0,0), (0,0), 10),
            ('LEFTPADDING', (0,0), (0,0), 12),
            ('RIGHTPADDING', (0,0), (0,0), 12),
        ]))
        
        story.append(KeepTogether([outer_card_table, Spacer(1, 12)]))
        
    # Build Document using NumberedCanvas
    doc.build(story, canvasmaker=NumberedCanvas)
    buffer.seek(0)
    return buffer
