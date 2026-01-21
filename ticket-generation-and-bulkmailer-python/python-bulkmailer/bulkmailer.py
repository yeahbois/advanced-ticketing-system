import time
from dotenv import load_dotenv
import os
import requests
import smtplib
import ssl
from email.message import EmailMessage


load_dotenv()

SMTP_HOST = "smtp.hostinger.com"
SMTP_PORT = 465  # SSL
DELAY_BETWEEN_EMAIL = 3  # seconds
ATTACHMENT_DIR = "output/tickets"  # where your tickets are
WEBSITE_URL = "https://dcff994a9809.ngrok-free.app"

# =========================
# LOAD ENV
# =========================

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

MAILBOXES = [
    {"email": os.getenv("EMAIL7"), "password": os.getenv("PASSWD7")}
]

# MAILBOXES = [
#     {"email": os.getenv("EMAIL1"), "password": os.getenv("PASSWD1")},
#     {"email": os.getenv("EMAIL2"), "password": os.getenv("PASSWD2")},
#     {"email": os.getenv("EMAIL3"), "password": os.getenv("PASSWD3")},
#     {"email": os.getenv("EMAIL4"), "password": os.getenv("PASSWD4")}
# ]

# MAILBOXES = [
#     {"email": os.getenv("EMAIL5"), "password": os.getenv("PASSWD5")},
#     {"email": os.getenv("EMAIL6"), "password": os.getenv("PASSWD6")}
# ]

# MAILBOXES = [
#     {"email": os.getenv("EMAIL6"), "password": os.getenv("PASSWD6")}
# ]

TICKET_ROLE_MAP = {
    "Tiket ANAK saja": ["ANAK"],
    "Tiket ANAK + 1 ORANG TUA/WALI": ["ANAK", "OTM1"],
    "Tiket ANAK + 2 ORANG TUA/WALI": ["ANAK", "OTM1", "OTM2"],
}

print("=== BulkMailer Open House E-Ticket ===")
print("Loaded mailboxes:")
for m in MAILBOXES:
    print("-", m["email"])

# =========================
# LOAD DATA
# =========================

def load_recipients(amount: int, start_index: int = 1):
    '''
    start_index = user ID (1-based)
    amount = how many users to fetch
    '''

    response = requests.get(f"{WEBSITE_URL}/api/oh/get-all-data-sql")
    response.raise_for_status()
    json_data = response.json()

    total_available = len(json_data)

    print(f"Step 1. Fetched total {total_available} recipients from API.")

    # Convert user ID → array index
    start_array_index = start_index - 1
    end_array_index = start_array_index + amount

    # Safety checks
    if start_array_index < 0 or start_array_index >= total_available:
        raise ValueError(f"start_index {start_index} is out of range.")

    # Slice safely
    data = json_data[start_array_index:end_array_index]

    print(f"Loaded recipients from ID {start_index} to ID {start_index + len(data) - 1}")

    return data


# =========================
# SEND EMAIL
# =========================

def send_email(mailbox, recipient_email, recipient_name, ticket_id, data):
    jenis_tiket = data['jenis_tiket']
    jenis_tiket_roles = TICKET_ROLE_MAP[jenis_tiket]

    msg = EmailMessage()
    msg["From"] = mailbox["email"]
    msg["To"] = recipient_email
    msg["Bcc"] = mailbox["email"]
    msg["Subject"] = "E-Ticket Open House SMAN Unggulan M.H. Thamrin 2026"

    msg.set_content(f"""
Halo {recipient_name},

Berikut adalah ringkasan pesanan tiket Anda:
Nama: {recipient_name}
Sekolah: {data['asal_sekolah']} // {data['kelas']}
Nomor WhatsApp: {data['nomor_whatsapp']}
Kelompok: {data['id_kelompok']} // {KELOMPOK[data['id_kelompok']]}
Jenis Tiket: {data['jenis_tiket']}

Dimohon untuk segera masuk ke grup WhatsApp utama untuk pengumuman lebih lanjut:
Siswa: https://chat.whatsapp.com/FVaYdO9Y4lMBut8uwfkfvg
{"Wali Murid: https://chat.whatsapp.com/G1TxkQVZph2BEmZroOdCXx" if data['jenis_tiket'] != 'Tiket ANAK saja' else ''}

Terima kasih telah mendaftar Open House SMAN Unggulan M.H. Thamrin 2026.

Terlampir adalah e-ticket Anda. Silakan simpan dan tunjukkan saat registrasi.
Catatan: Untuk tiket bersama wali, diharapkan wali memiliki salinan e-ticket sendiri, dapat berupa cetakan, atau screenshot layar.
{"CATATAN: Untuk tiket bersama 2 wali, diharapkan kedua wali masing-masing memiliki salinan e-ticket masing masing sendiri, akan dilampirkan 2 e-ticket untuk masing-masing wali yang perlu discan saat registrasi." if data['jenis_tiket'] == 'Tiket ANAK + 2 ORANG TUA/WALI' else ''}

Sampai jumpa di Open House!
Salam,

Panitia Open House
""")

    # Attach ticket
    for role in jenis_tiket_roles:
        ticket_filename = f"{role}_{ticket_id}.png"
        ticket_path = os.path.join(ATTACHMENT_DIR, ticket_filename)
        if not os.path.exists(ticket_path):
            print(f"[WARNING] Ticket file not found: {ticket_path}")
            continue
        with open(ticket_path, "rb") as f:
            file_data = f.read()
            msg.add_attachment(
                file_data,
                maintype="image",
                subtype="png",
                filename=ticket_filename
            )
            print(f"   - Attached ticket for role {role}: {ticket_filename}")

    context = ssl.create_default_context()

    try:
        with smtplib.SMTP_SSL(SMTP_HOST, SMTP_PORT, context=context) as server:
            server.login(mailbox["email"], mailbox["password"])
            server.send_message(msg)
            return True
    except Exception as e:
        print(f"[ERROR] Failed to send to {recipient_email}: {e}")
        return False

# =========================
# BULK LOGIC
# =========================

def bulk_send(recipients, max_per_mailbox=50):
    mailbox_index = 0
    sent_counter = [0] * len(MAILBOXES)

    total = len(recipients)
    print(f"\nTotal to send: {total}\n")

    stopped_early = False
    last_index = 0

    for i, person in enumerate(recipients, start=1):
        last_index = i
        mailbox = MAILBOXES[mailbox_index]

        # skip if this mailbox hit limit
        if sent_counter[mailbox_index] >= max_per_mailbox:
            mailbox_index += 1
            if mailbox_index >= len(MAILBOXES):
                print("All mailboxes reached daily limit. Stopping.")
                stopped_early = True
                break
            mailbox = MAILBOXES[mailbox_index]

        print(f"[{i}/{total}] Sending to {person['email']} ({person['jenis_tiket']}_{person['id_tiket']}) using {mailbox['email']}...")

        success = send_email(
            mailbox,
            person["email"],
            person["nama_lengkap"],
            person["id_tiket"],
            person
        )

        if success:
            sent_counter[mailbox_index] += 1
            print(f"   ✔ Sent. {mailbox['email']} used {sent_counter[mailbox_index]}/{max_per_mailbox}")
            try:
                update_response = requests.get(f"{WEBSITE_URL}/api/oh/update-ticket-status", params={"ticket_id": person["id_tiket"], "status": "Terkirim"})
                if update_response.status_code == 200:
                    print("   ✔ Ticket status updated successfully.")
                else:
                    print(f"   ✖ Failed to update ticket status. Status code: {update_response.status_code}")
            except Exception as e:
                print(f"   ✖ Exception while updating ticket status: {e}")
        else:
            requests.get(f"{WEBSITE_URL}/api/oh/update-ticket-status", params={"ticket_id": person["id_tiket"], "status": f"Gagal Terkirim"})
            print("   ✖ Failed.")

        time.sleep(DELAY_BETWEEN_EMAIL)

    # 🔽 PUT IT HERE
    if stopped_early:
        print(f"\n⚠ Stopped at {last_index-1}/{total}. Resume tomorrow from index {last_index}.")

    print("\n=== SUMMARY ===")
    for idx, count in enumerate(sent_counter):
        print(f"{MAILBOXES[idx]['email']} -> {count} emails sent")

# =========================
# MAIN
# =========================

if __name__ == "__main__":
    '''
    START INDEX 0 = FIRST ID (ID 1)
    '''
    recipients = load_recipients(amount=5, start_index=542)

    # Day 1 example: 50 per mailbox = 200 total
    '''
    MB 1: 97-50= 47
    MB 2: 97-50=
    MB 3: 97-50=
    MB 4: 97-50=

    only 140 LEFT
    MB 5: 70
    MB 6: 70
    '''
    # Day 2: max = 98
    bulk_send(recipients, max_per_mailbox=90)
    # send_email(
    #     MAILBOXES[0],
    #     "marcellolienarta663@gmail.com",
    #     "Marcello Lienarta",
    #     recipients[1]['id_tiket'],
    #     recipients[1]
    # )
    print("=== DONE ===")