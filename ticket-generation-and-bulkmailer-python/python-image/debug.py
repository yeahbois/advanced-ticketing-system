# # import os
# # import requests

# # WEBSITE_URL = "https://3a85332cf010.ngrok-free.app"
# # OUTPUT_DIR = "output"
# # OUTPUT_TICKET_DIR = "output/tickets"
# # OUTPUT_QR_DIR = "output/qr"

# # plain_data = requests.get(f"{WEBSITE_URL}/api/oh/get-all-data-sql")
# # data = plain_data.json()

# # counter = 0
# # IDTIKETOTM = []

# # for i in data:
# #     if i['jenis_tiket'] == 'Tiket ANAK + 1 ORANG TUA/WALI':
# #         counter +=1
# #         IDTIKETOTM.append(f"OTM1_{i['id_tiket']}.png")

# # TIKETOTM = [f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('OTM1_')]
# # print(f"TIKET OTM1 DI DB: {IDTIKETOTM}")
# # print(f"TIKET OTM1 DI FOLDER: {TIKETOTM}")
# # #folder-db
# # tCount = 0
# # for t in TIKETOTM:
# #     if t not in IDTIKETOTM:
# #         tCount += 1
# #         print(f"TIDAK ADA DI DB: {tCount}. {t}")

# # print(f'''
# # Total Tiket ANAK:
# # {counter}
# # {len([f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('ANAK_')])}
# # Total Tiket OTM1:
# # {len([f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('OTM1_')])}
# # Total Tiket OTM2:
# # {len([f for f in os.listdir(OUTPUT_TICKET_DIR) if f.startswith('OTM2_')])}
# # ''')

# test = "no"

# print(f'''
# {"nigga "if test=="hello" else "no"}
# ''')

sent_counter = [0] * 2
print(sent_counter)
