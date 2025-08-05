import csv
import time
import chromedriver_autoinstaller
import requests
from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# Auto-install matching Chromedriver
chromedriver_autoinstaller.install()

LIST_URL = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

# Prepare a requests session for detail‐page fetching
session = requests.Session()
session.headers.update({
    "User-Agent": "Mozilla/5.0",
    "Referer": "https://www.rkguns.com/",
})
# Bypass age-gate
session.cookies.set("hasVerifiedAge", "true", domain="www.rkguns.com")

def fetch_details(url):
    """Fetch detail page via requests + parse the spec table."""
    r = session.get(url)
    r.raise_for_status()
    soup = BeautifulSoup(r.text, "html.parser")
    # table rows look like <tr><th>UPC</th><td>022188891508</td></tr>
    specs = {th.get_text(strip=True): td.get_text(strip=True)
             for row in soup.select("table.product-specs tr")
             if (th := row.select_one("th")) and (td := row.select_one("td"))}
    return {
        "UPC": specs.get("UPC", ""),
        "MPN": specs.get("MPN", specs.get("Manufacturer Part Number", "")),
        "Caliber": specs.get("Caliber", ""),
        # Map “Class” → Type
        "Type": specs.get("Class", "")
    }

def main():
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait = WebDriverWait(driver, 15)

    # Seed age gate cookie in Selenium too
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES+1):
            print(f"→ Page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(LIST_URL.format(page))

            # wait for product‐cards
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))
            cards = driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")
            print(f"   Found {len(cards)} cards", flush=True)

            for card in cards:
                detail_href = card.get_attribute("href")
                name = card.get_attribute("data-cnstrc-item-name").strip()

                # Fetch the detail page via requests
                details = fetch_details(detail_href)
                writer.writerow({
                    "Brand/Model": name,
                    **details
                })

    driver.quit()
    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
