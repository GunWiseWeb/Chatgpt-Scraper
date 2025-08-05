import csv
import time
import chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

chromedriver_autoinstaller.install()

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

def main():
    print("🚀 Starting scraper", flush=True)
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait = WebDriverWait(driver, 15)

    # Pre-set age-verification cookie
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name": "hasVerifiedAge", "value": "true", "domain": "www.rkguns.com"})
    print("🔓 Age gate cookie set", flush=True)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES + 1):
            print(f"→ Loading page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(URL_TEMPLATE.format(page))

            # Wait for at least one item
            try:
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, ".cio-item")))
            except:
                print(f"⚠️  No products on page {page}", flush=True)
                continue

            items = driver.find_elements(By.CSS_SELECTOR, ".cio-item")
            print(f"   Found {len(items)} items", flush=True)

            for itm in items:
                name = itm.find_element(By.CSS_SELECTOR, ".cio-item-name").text

                # TODO: Update these selectors to whatever the live page uses now:
                upc = itm.find_element(By.CSS_SELECTOR, ".cio-item-upc").text if itm.find_elements(By.CSS_SELECTOR, ".cio-item-upc") else ""
                mpn = itm.find_element(By.CSS_SELECTOR, ".cio-item-mpn").text if itm.find_elements(By.CSS_SELECTOR, ".cio-item-mpn") else ""
                caliber = itm.find_element(By.CSS_SELECTOR, ".cio-item-caliber").text if itm.find_elements(By.CSS_SELECTOR, ".cio-item-caliber") else ""
                ftype = itm.find_element(By.CSS_SELECTOR, ".cio-item-type").text if itm.find_elements(By.CSS_SELECTOR, ".cio-item-type") else ""

                writer.writerow({
                    "Brand/Model": name,
                    "UPC": upc,
                    "MPN": mpn,
                    "Caliber": caliber,
                    "Type": ftype
                })

    driver.quit()
    print("✅ Scraping complete", flush=True)

if __name__ == "__main__":
    main()
