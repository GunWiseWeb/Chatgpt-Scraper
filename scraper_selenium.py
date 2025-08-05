# File: scraper_selenium.py

import csv
import time
import chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup

# Auto-install matching Chromedriver
chromedriver_autoinstaller.install()

LIST_URL    = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS      = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

def parse_detail_page(html: str):
    soup = BeautifulSoup(html, "html.parser")
    # Title
    title_el = soup.select_one("h1.page-title")
    title    = title_el.get_text(strip=True) if title_el else ""

    # Specs
    specs = {}
    for row in soup.select("table.product-specs tr"):
        th = row.select_one("th")
        td = row.select_one("td")
        if th and td:
            specs[th.get_text(strip=True)] = td.get_text(strip=True)

    # Description & infer type
    desc_el = soup.select_one(".product-description") or soup.select_one("#description")
    desc = desc_el.get_text(" ", strip=True).lower() if desc_el else ""
    for kind in ("pistol", "revolver", "rifle", "shotgun"):
        if kind in desc:
            ptype = kind.capitalize()
            break
    else:
        ptype = "Other"

    return {
        "Title":      title,
        "Brand":      specs.get("Brand", ""),
        "Model Name": specs.get("Model Name", specs.get("Model", "")),
        "UPC":        specs.get("UPC", ""),
        "MPN":        specs.get("MPN", specs.get("Manufacturer Part Number", "")),
        "Caliber":    specs.get("Caliber", ""),
        "Type":       ptype
    }

def main():
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait   = WebDriverWait(driver, 15)

    # Seed age-gate
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
                href = card.get_attribute("href")
                print("     Fetching detail:", href, flush=True)
                driver.get(href)
                # wait for specs table or title
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "h1.page-title")))

                detail_html = driver.page_source
                data = parse_detail_page(detail_html)
                writer.writerow(data)
                # go back to listing
                driver.back()
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))

    driver.quit()
    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
