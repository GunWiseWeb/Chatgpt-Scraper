import csv
import time
import re
import chromedriver_autoinstaller
import requests
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Auto‐install matching chromedriver
chromedriver_autoinstaller.install()

LIST_URL    = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS      = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

# Shared Requests session for detail‐page fetches
session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0",
    "Referer": "https://www.rkguns.com/",
})
session.cookies.set("hasVerifiedAge", "true", domain="www.rkguns.com")

def infer_type(description_text: str) -> str:
    desc = description_text.lower()
    for kind in ("pistol", "revolver", "rifle", "shotgun"):
        if kind in desc:
            return kind.capitalize()
    return "Other"

def fetch_details(url: str) -> dict:
    """Fetch and parse the detail page for specs & description."""
    r = session.get(url)
    r.raise_for_status()
    soup = BeautifulSoup(r.text, "html.parser")

    # Title = <h1 class="page-title">…</h1>
    title_el = soup.select_one("h1.page-title")
    title    = title_el.get_text(strip=True) if title_el else ""

    # Specs table rows: <tr><th>Brand</th><td>Smith & Wesson</td></tr>
    specs = {}
    for row in soup.select("table.product-specs tr"):
        th = row.select_one("th")
        td = row.select_one("td")
        if th and td:
            specs[th.get_text(strip=True)] = td.get_text(strip=True)

    # Description block
    desc_el = soup.select_one(".product-description") or soup.select_one("#description")
    description = desc_el.get_text(" ", strip=True) if desc_el else ""

    return {
        "Title":       title,
        "Brand":       specs.get("Brand", ""),
        "Model Name":  specs.get("Model Name", specs.get("Model", "")),
        "UPC":         specs.get("UPC", ""),
        "MPN":         specs.get("MPN", specs.get("Manufacturer Part Number", "")),
        "Caliber":     specs.get("Caliber", ""),
        "Type":        infer_type(description)
    }

def main():
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait   = WebDriverWait(driver, 15)

    # Seed age‐gate cookie
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES+1):
            print(f"→ Listing page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(LIST_URL.format(page))
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))

            cards = driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")
            print(f"   Found {len(cards)} products", flush=True)

            for card in cards:
                detail_url = card.get_attribute("href")
                data = fetch_details(detail_url)
                writer.writerow(data)
                time.sleep(0.2)  # gentle throttle

    driver.quit()
    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
