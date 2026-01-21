from unicodedata import category
import requests
from PIL import Image, ImageDraw, ImageFont
import qrcode
import os

OUTPUT_DIR = "output"
OUTPUT_TICKET_DIR = "output/tickets"
OUTPUT_QR_DIR = "output/qr"
TEMPLATE_PATH = "assets/template/template_e_ticket.png"
FONT_DIR = "assets/font/HelveticaNowText-Bold.ttf"
WEBSITE_URL = "https://dcff994a9809.ngrok-free.app"


plain_data = requests.get(f"{WEBSITE_URL}/api/oh/get-all-data-sql")
print("-- SISTEM AUTOTICKET OH --")
print(f"Step 1. Ambil data dari database: {WEBSITE_URL}/api/oh/get-all-data-sql. Status: {plain_data.status_code}")
print(f"Jumlah data diterima: {len(plain_data.json())} entri.")

TICKET_ROLE_MAP = {
    "Tiket ANAK saja": ["ANAK"],
    "Tiket ANAK + 1 ORANG TUA/WALI": ["ANAK", "OTM1"],
    "Tiket ANAK + 2 ORANG TUA/WALI": ["ANAK", "OTM1", "OTM2"],
}

KELOMPOK = {
    "1": "Timun Mas",
    "2": "Buto Ijo",
    "3": "Roro Jonggrang",
    "4": "Sangkuriang",
    "5": "Si Pitung",
    "6": "Bandung Bondowoso",
    "7": "Gajah Mada",
    "8": "Jaka Tarub",
    "9": "Lutung Kasarung",
    "10": "Gatot Kaca",
    "11": "Prabu Siliwangi",
    "12": "Bawang Merah",
    "13": "Bawang Putih",
    "14": "Keong Mas",
    "15": "Joko Kendil",
    "16": "Putri Junjung Buih",
    "17": "Cindelaras",
    "18": "Purbasari",
    "19": "Ratu Cik Sima",
    "20": "Manik Angkeran",
    "21": "Ciung Wanara",
    "22": "Ande Ande Lumut",
    "23": "Ikan Sura",
    "24": "Buaya Baya",
    "25": "Si Kancil"
}

def generate_qr(role, ticket_id):
    qr = qrcode.make(f"{role}_{ticket_id}")
    qr_path = f"{OUTPUT_DIR}/qr/{role}_{ticket_id}.png"
    qr.save(qr_path)
    return qr_path

def build_templates(data):
    return {
        "ANAK": {
            "nama": data['nama_lengkap'],
            "jenis_tiket": data['jenis_tiket'],
            "tanggal": "Minggu, 11 Januari 2026",
            "ruangan": data['id_kelompok'],
            "ticketID": data['id_tiket']
        },
        "OTM1": {
            "nama": data['nama_lengkap'],
            "jenis_tiket": data['jenis_tiket'],
            "tanggal": "Minggu, 11 Januari 2026",
            "ruangan": data['ruang_otm1'],
            "ticketID": data['id_tiket']
        },
        "OTM2": {
            "nama": data['nama_lengkap'],
            "jenis_tiket": data['jenis_tiket'],
            "tanggal": "Minggu, 11 Januari 2026",
            "ruangan": data['ruang_otm2'],
            "ticketID": data['id_tiket']
        }
    }

def create_qr(data, index, total):
    jenis_tiket = data['jenis_tiket']

    if jenis_tiket not in TICKET_ROLE_MAP:
        raise ValueError(f"Jenis tiket tidak dikenali: {jenis_tiket}")

    templates = build_templates(data)
    roles = TICKET_ROLE_MAP[jenis_tiket]

    for role in roles:
        tmp = templates[role]
        qr_path = generate_qr(role, tmp['ticketID'])
        print(f"{index}/{total} - QR Code generated and saved to {qr_path} JENIS {role}")

def wrap_text(text, max_chars=20):
    words = text.split(" ")
    lines = []
    current_line = ""

    for word in words:
        if len(current_line + " " + word) <= max_chars:
            if current_line == "":
                current_line = word
            else:
                current_line += " " + word
        else:
            lines.append(current_line)
            current_line = word

    if current_line:
        lines.append(current_line)

    return "\n".join(lines)
    
def create_ticket(data, index, total):
    jenis_tiket = data['jenis_tiket']

    if jenis_tiket not in TICKET_ROLE_MAP:
        raise ValueError(f"Jenis tiket tidak dikenali: {jenis_tiket}")

    roles = TICKET_ROLE_MAP[jenis_tiket]
    templates = build_templates(data)

    for role in roles:
        tmp = templates[role]
        ticket_id = tmp['ticketID']

        # Load base ticket template
        base = Image.open(TEMPLATE_PATH).convert("RGBA")
        draw = ImageDraw.Draw(base)

        # Fonts (adjust path & size if needed)
        font_big = ImageFont.truetype(FONT_DIR, 36)
        font_mid = ImageFont.truetype(FONT_DIR, 33)
        font_small = ImageFont.truetype(FONT_DIR, 22)

        # === RANDOM COORDINATES (can be adjusted later) ===
        NAME_POS = (135, 455)
        TYPE_POS = (780, 455)
        DATE_POS = (135, 720)
        ROOM_POS = (780, 720)

        QR_POS = (445, 915)   # where QR will be pasted

        # Draw text
        wrapped_name = wrap_text(tmp['nama'].upper(), max_chars=20)
        draw.text(NAME_POS, wrapped_name, fill="black", font=font_mid)
        #draw.multiline_text(NAME_POS, wrapped_name, fill="black", font=font_mid, spacing=4)
        draw.text(TYPE_POS, f"{role}", fill="black", font=font_mid)
        draw.text(DATE_POS, f"{tmp['tanggal']}", fill="black", font=font_mid)
        if role == "ANAK":
            draw.text(ROOM_POS, f"{tmp['ruangan']} // {KELOMPOK[tmp['ruangan']]}", fill="black", font=font_mid)
        else:
            draw.text(ROOM_POS, f"{tmp['ruangan']}", fill="black", font=font_mid)
        # Load QR
        qr_path = f"{OUTPUT_QR_DIR}/{role}_{ticket_id}.png"
        if not os.path.exists(qr_path):
            raise FileNotFoundError(f"QR tidak ditemukan: {qr_path}")

        qr_img = Image.open(qr_path).convert("RGBA")
        qr_img = qr_img.resize((355, 355), Image.NEAREST)  # resize for ticket

        # Paste QR
        base.paste(qr_img, QR_POS, qr_img)

        # Save ticket
        output_path = f"{OUTPUT_TICKET_DIR}/{role}_{ticket_id}.png"
        base.save(output_path)

        print(f"{index}/{total} - E-Ticket generated and saved to {output_path} JENIS {role}")


print("Step 2. Generate QR Code...")
plain_json = plain_data.json()
total_data = len(plain_json)
for i, entry in enumerate(plain_json, start=1):
    create_qr(entry, i, total_data)
print("Semua QR Code berhasil dibuat.")

print("Step 3. Desain e-ticket...")
for i, entry in enumerate(plain_json, start=1):
    create_ticket(entry, i, total_data)
print("Semua e-ticket berhasil dibuat.")

print("Step 4. Check ulang...")
print(f'''
Total Data: {total_data}
Total QR Code di folder {OUTPUT_QR_DIR}: {len(os.listdir(OUTPUT_QR_DIR))}
Total E-Ticket di folder {OUTPUT_TICKET_DIR}: {len(os.listdir(OUTPUT_TICKET_DIR))}
Total Tiket ANAK:
{len([f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('ANAK_')])}
Total Tiket OTM1:
{len([f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('OTM1_')])}
Total Tiket OTM2:
{len([f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('OTM2_')])}
''')
print("Selesai! Terima kasih!")

