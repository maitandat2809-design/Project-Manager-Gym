from pypdf import PdfReader

reader = PdfReader(r"C:\xampp\htdocs\Gym Manager\objectives-review.pdf")
for page_number, page in enumerate(reader.pages, start=1):
    text = page.extract_text() or ""
    if "1.3 Objectives" in text or "1.3.1 Develop a Gym" in text or "1.3.4 Expanding" in text:
        print("--- PAGE", page_number, "---")
        print(text[:7000])
