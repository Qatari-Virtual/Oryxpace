import io
import os
import json
from django.conf import settings
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether, Image
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
        
        system_text = "Oryxspace - Infinite Flight Simulator Evaluation System"
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
    
    # Set up modern airline training record palette
    primary_color = colors.HexColor("#5C0E3E")    # Qatar Maroon Header
    secondary_color = colors.HexColor("#8B1538")  # Accent Maroon
    success_color = colors.HexColor("#166534")    # Dark Green Success
    danger_color = colors.HexColor("#B91C1C")     # Red Error / Status
    warning_color = colors.HexColor("#B45309")    # Amber Warning
    border_color = colors.HexColor("#E5E7EB")     # Gray border
    main_text_color = colors.HexColor("#1F2937")  # Main Text (#1F2937)
    
    # Block Styling Colors (Background + Text)
    obs_bg = colors.HexColor("#FEE2E2")           # Observation background (Soft Red)
    obs_text = colors.HexColor("#991B1B")         # Observation text
    std_bg = colors.HexColor("#EFF6FF")           # Expected Standard background (Soft Blue)
    std_text = colors.HexColor("#1E40AF")         # Expected Standard text
    rem_bg = colors.HexColor("#F8FAFC")           # Remarks background (Soft slate-50)
    rem_text = colors.HexColor("#334155")         # Remarks text
    
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
        textColor=main_text_color
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
    total_score = sum(item.get("points", 1) for item in data["answers"] if not item.get("is_optional", False))
    score_earned = sum(item.get("points", 1) for item in data["answers"] if item.get("is_correct", False) and not item.get("is_optional", False))
    grade_percentage = (score_earned / total_score * 100) if total_score > 0 else 0
    passed = data.get("final_decision", "PASS").upper() == "PASS"
    
    # Document Header with Qatar Virtual Brand Logo
    logo_path = os.path.join(settings.BASE_DIR.parent, 'assets', 'Qatar_Virtual_logo.PNG')
    title_p = Paragraph("FLIGHT EVALUATION REPORT", title_style)
    subtitle_p = Paragraph("Infinite Flight Simulator Trainee Performance Checklist", subtitle_style)
    titles_container = [title_p, subtitle_p]
    
    if os.path.exists(logo_path):
        try:
            # Let's set a standard height and width to maintain aspect ratio (3:1)
            logo_img = Image(logo_path, width=120, height=40)
            header_table = Table([[titles_container, logo_img]], colWidths=[364, 140])
            header_table.setStyle(TableStyle([
                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                ('ALIGN', (0,0), (0,0), 'LEFT'),
                ('ALIGN', (1,0), (1,0), 'RIGHT'),
                ('LEFTPADDING', (0,0), (-1,-1), 0),
                ('RIGHTPADDING', (0,0), (-1,-1), 0),
                ('BOTTOMPADDING', (0,0), (-1,-1), 0),
                ('TOPPADDING', (0,0), (-1,-1), 0),
            ]))
            story.append(header_table)
        except Exception as e:
            print(f"Error adding logo to PDF: {e}")
            story.append(title_p)
            story.append(subtitle_p)
    else:
        story.append(title_p)
        story.append(subtitle_p)
        
    story.append(Spacer(1, 15))
    
    # 2-Column Metadata & Score Block
    # Widths total 504 pt (Letter width 612 - margins 108)
    meta_col_width = 354
    score_col_width = 150
    
    # Metadata sub-table
    meta_data = [
        [Paragraph("TRAINEE PILOT", meta_label_style), Paragraph("FLIGHT INSTRUCTOR (FI)", meta_label_style)],
        [
            Paragraph(f"<b>{data['trainee_name']}</b> ({data['trainee_callsign']})", meta_val_style), 
            Paragraph(f"<b>{data['instructor_name']}</b> ({data.get('instructor_callsign', 'QRV')})", meta_val_style)
        ],
        [Spacer(1, 4), Spacer(1, 4)],
        [Paragraph("EVALUATION DATE", meta_label_style), ""],
        [Paragraph(data['flight_date'], meta_val_style), ""]
    ]
    meta_sub_table = Table(meta_data, colWidths=[177, 177])
    meta_sub_table.setStyle(TableStyle([
        ('ALIGN', (0,0), (-1,-1), 'LEFT'),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('SPAN', (0, 3), (1, 3)),
        ('SPAN', (0, 4), (1, 4)),
        ('BOTTOMPADDING', (0,0), (-1,-1), 1),
        ('TOPPADDING', (0,0), (-1,-1), 1),
    ]))
    
    # Scorecard Panel
    status_paragraph = Paragraph("PASS", status_pass_style) if passed else Paragraph("RETAKE", status_fail_style)
    score_data = [
        [Paragraph("FINAL GRADE", score_label_style)],
        [Paragraph(f"{grade_percentage:.1f}%", score_val_style)],
        [Spacer(1, 2)],
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
        ('BACKGROUND', (1,0), (1,0), colors.HexColor("#fdf8f9")),
        ('BOX', (1,0), (1,0), 1, colors.HexColor("#ead9df")),
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
    
    # Load procedure guides mapping (optional guides/photos for wrong answers)
    guides_map = {}
    guides_json_path = os.path.join(settings.BASE_DIR, 'apps', 'checkride', 'data', 'procedure_guides.json')
    if os.path.exists(guides_json_path):
        try:
            with open(guides_json_path, 'r', encoding='utf-8') as f:
                guides_map = json.load(f)
        except Exception as e:
            print(f"Error loading procedure guides JSON: {e}")

    # Sort answers alphabetically by ID (FP1 -> FP2 -> FP3 -> FP4) for logical flight phase sequence in PDF
    sorted_answers = sorted(data["answers"], key=lambda x: x.get("id", ""))
    
    # Loop over categories/answers
    for idx, ans in enumerate(sorted_answers):
        if ans.get("is_optional", False):
            continue
        question_block = []
        
        # Header for the question item
        item_status_color = success_color if ans["is_correct"] else danger_color
        
        if ans["is_correct"]:
            status_text = "<font color='#166534'><b>✓ Correct</b></font>"
        else:
            status_text = "<font color='#B91C1C'><b>✗ Needs Improvement</b></font>"
            
        header_text = f"<b>{ans['category']}</b>"
        
        # Item Header Layout
        header_p = Paragraph(header_text, ParagraphStyle('ItemH', parent=body_style, fontName='Helvetica-Bold', fontSize=11, leading=14, textColor=primary_color))
        status_p = Paragraph(status_text, ParagraphStyle('ItemS', parent=body_style, alignment=TA_RIGHT, fontSize=10, fontName='Helvetica-Bold'))
        
        item_header_table = Table([[header_p, status_p]], colWidths=[330, 150])
        
        t_style = [
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('BOTTOMPADDING', (0,0), (-1,-1), 6),
            ('TOPPADDING', (0,0), (-1,-1), 6),
        ]
        
        # If wrong, or if we have custom feedback remarks, draw a thin divider line
        if not ans["is_correct"] or ans.get("extra_feedback"):
            t_style.append(('LINEBELOW', (0,0), (-1,-1), 0.5, border_color))
            
        item_header_table.setStyle(TableStyle(t_style))
        question_block.append(item_header_table)
        
        # If wrong, print the trainee's selected wrong action (Observation) and standard (Expected Standard)
        if not ans["is_correct"]:
            question_block.append(Spacer(1, 8))
            
            selected_text = ans['selected_option']
            if ans.get("is_other", False) and ans.get("other_text"):
                selected_text = f"Other: {ans['other_text']}"
                
            # 1. Observation Block
            obs_style = ParagraphStyle('ObsStyle', parent=body_style, textColor=obs_text, leading=14.5)
            obs_content = f"<b>⚠ Observation</b><br/>{selected_text}"
            obs_p = Paragraph(obs_content, obs_style)
            obs_table = Table([[obs_p]], colWidths=[480])
            obs_table.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), obs_bg),
                ('BOX', (0,0), (0,0), 0.5, colors.HexColor("#FCA5A5")),
                ('TOPPADDING', (0,0), (0,0), 6),
                ('BOTTOMPADDING', (0,0), (0,0), 6),
                ('LEFTPADDING', (0,0), (0,0), 10),
                ('RIGHTPADDING', (0,0), (0,0), 10),
            ]))
            question_block.append(obs_table)
            question_block.append(Spacer(1, 8))
            
            # 2. Expected Standard Block
            std_style = ParagraphStyle('StdStyle', parent=body_style, textColor=std_text, leading=14.5)
            std_content = f"<b>✓ Expected Standard</b><br/>{ans['procedure_details']}"
            std_p = Paragraph(std_content, std_style)
            std_table = Table([[std_p]], colWidths=[480])
            std_table.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), std_bg),
                ('BOX', (0,0), (0,0), 0.5, colors.HexColor("#93C5FD")),
                ('TOPPADDING', (0,0), (0,0), 6),
                ('BOTTOMPADDING', (0,0), (0,0), 6),
                ('LEFTPADDING', (0,0), (0,0), 10),
                ('RIGHTPADDING', (0,0), (0,0), 10),
            ]))
            question_block.append(std_table)
            
            # 2b. Explanatory Photo (only shown if incorrect and image path mapped in guides_map)
            q_id = ans.get("id")
            if q_id in guides_map:
                guide_info = guides_map[q_id]
                rel_img_path = guide_info.get("image_path")
                if rel_img_path:
                    abs_img_path = os.path.join(settings.BASE_DIR.parent, rel_img_path)
                    if os.path.exists(abs_img_path):
                        try:
                            question_block.append(Spacer(1, 8))
                            img_w = guide_info.get("display_width", 380)
                            img_h = guide_info.get("display_height", 180)
                            guide_img = Image(abs_img_path, width=img_w, height=img_h)
                            
                            # Center the image
                            img_table = Table([[guide_img]], colWidths=[480])
                            img_table.setStyle(TableStyle([
                                ('ALIGN', (0,0), (-1,-1), 'CENTER'),
                                ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
                                ('LEFTPADDING', (0,0), (-1,-1), 0),
                                ('RIGHTPADDING', (0,0), (-1,-1), 0),
                                ('TOPPADDING', (0,0), (-1,-1), 0),
                                ('BOTTOMPADDING', (0,0), (-1,-1), 0),
                            ]))
                            question_block.append(img_table)
                            
                            # Caption
                            caption_text = guide_info.get("caption")
                            if caption_text:
                                caption_style = ParagraphStyle(
                                    'ImgCaption', 
                                    parent=body_style, 
                                    fontName='Helvetica-Oblique', 
                                    fontSize=8, 
                                    textColor=colors.HexColor("#64748b"),
                                    alignment=TA_CENTER
                                )
                                question_block.append(Spacer(1, 4))
                                question_block.append(Paragraph(caption_text, caption_style))
                        except Exception as img_err:
                            print(f"Error rendering guide image in PDF: {img_err}")
            
        # 3. Instructor Remarks Block (if present)
        if ans.get("extra_feedback"):
            question_block.append(Spacer(1, 8))
            
            rem_style = ParagraphStyle('RemStyle', parent=body_style, textColor=rem_text, leading=14.5)
            rem_content = f"<b>📝 Instructor Remarks</b><br/>{ans['extra_feedback']}"
            rem_p = Paragraph(rem_content, rem_style)
            rem_table = Table([[rem_p]], colWidths=[480])
            rem_table.setStyle(TableStyle([
                ('BACKGROUND', (0,0), (0,0), rem_bg),
                ('BOX', (0,0), (0,0), 0.5, colors.HexColor("#CBD5E1")),
                ('TOPPADDING', (0,0), (0,0), 6),
                ('BOTTOMPADDING', (0,0), (0,0), 6),
                ('LEFTPADDING', (0,0), (0,0), 10),
                ('RIGHTPADDING', (0,0), (0,0), 10),
            ]))
            question_block.append(rem_table)
            
        # Outer Card Wrapper for the entire Checklist Item (Clean White background)
        outer_card_table = Table([[question_block]], colWidths=[504])
        outer_card_table.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (0,0), colors.white),
            ('BOX', (0,0), (0,0), 1, border_color),
            ('LINELEFT', (0,0), (0,0), 4, item_status_color),
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
