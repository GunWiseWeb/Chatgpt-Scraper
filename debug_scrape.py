# debug_scrape.py

import requests
from bs4 import BeautifulSoup

URL = "https://www.rkguns.com/firearms.html?page=1&numResults=36"
HEADERS = {
    "User-Agent": "Mozilla/5.0",
    "Cookie": "hasVerifiedAge=true"
}

r = requests.get(URL, headers=HEADERS)
r.raise_for_status()

soup = BeautifulSoup(r.text, "html.parser")
items = soup.select("div.cio-item-name")
print(f"Found {len(items)} name elements")
for i, itm in enumerate(items[:5], 1):
    print(f"{i}: {itm.get_text(strip=True)}")
