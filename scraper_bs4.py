import requests
from bs4 import BeautifulSoup
import csv
import math

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
HEADERS = {
    "User-Agent": "Mozilla/5.0",
    "Cookie": "hasVerifiedAge=true"
}
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]

def parse_page(html):
    soup = BeautifulSoup(html, "html.parser")
    products = soup.select("a.cio-product-card")
    rows = []
    for a in products:
        brand_model = a["data-cnstrc-item-name"]
        upc = a.get("data-cnstrc-item-id", "")  # if they used item-id for UPC
        # But more likely UPC is in a separate data attribute:
        upc = a.get("data-cnstrc-item-upc", "") or a.get("data-cnstrc-item-id", "")
        mpn = a.get("data-cnstrc-item-mpn", "")
        # Look for caliber and type inside the card
        caliber_el = a.select_one(".cio-item-caliber")
        typ_el    = a.select_one(".cio-item-type")
        caliber = caliber_el.get_text(strip=True) if caliber_el else ""
        ftype   = typ_el.get_text(strip=True)    if typ_el    else ""
        rows.append({
            "Brand/Model": brand_model,
            "UPC": upc,
            "MPN": mpn,
            "Caliber": caliber,
            "Type": ftype
        })
    return rows

def main():
    # First fetch page 1 to count total items
    r = requests.get(URL_TEMPLATE.format(1), headers=HEADERS)
    r.raise_for_status()
    # They don’t expose total count easily, so hard-code 278 pages
    total_pages = 278

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, total_pages+1):
            print(f"Fetching page {page}/{total_pages}")
            r = requests.get(URL_TEMPLATE.format(page), headers=HEADERS)
            r.raise_for_status()
            rows = parse_page(r.text)
            print(f" → Found {len(rows)} products")
            writer.writerows(rows)

if __name__ == "__main__":
    main()
