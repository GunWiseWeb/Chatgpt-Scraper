import requests
from bs4 import BeautifulSoup
import csv

BASE_URL = "https://www.rkguns.com/firearms.html"
OUTPUT_FILE = "inventory.csv"

HEADERS = {
    "User-Agent": "Mozilla/5.0 (compatible; InventoryScraper/1.0)"
}

FIELDS = ["UPC", "MPN", "Caliber", "Type"]

def parse_page(page_num):
    params = {"page": page_num, "numResults": 36}
    response = requests.get(BASE_URL, headers=HEADERS, params=params)
    response.raise_for_status()
    soup = BeautifulSoup(response.text, "html.parser")
    items = soup.select(".product-item")
    results = []
    for item in items:
        upc = item.select_one(".upc").get_text(strip=True) if item.select_one(".upc") else ""
        mpn = item.select_one(".mpn").get_text(strip=True) if item.select_one(".mpn") else ""
        caliber = item.select_one(".caliber").get_text(strip=True) if item.select_one(".caliber") else ""
        ftype = item.select_one(".firearm-type").get_text(strip=True) if item.select_one(".firearm-type") else ""
        results.append({"UPC": upc, "MPN": mpn, "Caliber": caliber, "Type": ftype})
    return results

def main():
    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=FIELDS)
        writer.writeheader()
        for page in range(1, 279):  # pages 1 through 278
            print(f"Scraping page {page}")
            try:
                rows = parse_page(page)
                writer.writerows(rows)
            except Exception as e:
                print(f"Error on page {page}: {e}")

if __name__ == "__main__":
    main()
