import csv
import time
import requests
from bs4 import BeautifulSoup

BASE_LIST_URL = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE   = "inventory.csv"
FIELDS        = ["Title","Brand","Model Name","UPC","MPN","Caliber","Type"]
TOTAL_PAGES   = 278
HEADERS = {
    "User-Agent": "Mozilla/5.0",
    "Cookie": "hasVerifiedAge=true"
}

def infer_type(text):
    t = text.lower()
    for kind in ("pistol","revolver","rifle","shotgun"):
        if kind in t:
            return kind.capitalize()
    return "Other"

def parse_detail(html):
    soup = BeautifulSoup(html, "html.parser")
    title_el = soup.select_one("h1.page-title")
    title    = title_el.get_text(strip=True) if title_el else ""
    # specs table:
    specs = {}
    for tr in soup.select("table.product-specs tr"):
        th = tr.select_one("th")
        td = tr.select_one("td")
        if th and td:
            specs[th.get_text(strip=True)] = td.get_text(strip=True)
    desc_el = soup.select_one(".product-description") or soup.select_one("#description")
    desc = desc_el.get_text(" ", strip=True) if desc_el else ""
    return {
        "Title":      title,
        "Brand":      specs.get("Brand",""),
        "Model Name": specs.get("Model Name", specs.get("Model","")),
        "UPC":        specs.get("UPC",""),
        "MPN":        specs.get("MPN", specs.get("Manufacturer Part Number","")),
        "Caliber":    specs.get("Caliber",""),
        "Type":       infer_type(desc)
    }

def main():
    session = requests.Session()
    session.headers.update(HEADERS)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES+1):
            print(f"→ Listing page {page}/{TOTAL_PAGES}")
            r = session.get(BASE_LIST_URL.format(page))
            r.raise_for_status()
            list_soup = BeautifulSoup(r.text, "html.parser")

            cards = list_soup.select("a.cio-product-card")
            print(f"   Found {len(cards)} cards")

            for a in cards:
                href = a.get("href")
                if not href.startswith("http"):
                    href = "https://www.rkguns.com" + href
                print("     Fetching", href)
                dr = session.get(href)
                dr.raise_for_status()
                data = parse_detail(dr.text)
                writer.writerow(data)
                time.sleep(0.1)  # small throttle

    print("✅ Done — inventory.csv created")

if __name__ == "__main__":
    main()
