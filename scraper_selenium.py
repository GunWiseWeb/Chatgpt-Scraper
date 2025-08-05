import csv
import time
import chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# 1) Auto-install matching Chromedriver
chromedriver_autoinstaller.install()

URL_TEMPLATE = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278  # known total count

def main():
    # 2) Configure headless Chrome
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait = WebDriverWait(driver, 15)

    # 3) Bypass age gate via cookie before navigating
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})

    # 4) Prepare CSV
    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as csvfile:
        writer = csv.DictWriter(csvfile, fieldnames=FIELDS)
        writer.writeheader()

        # 5) Loop pages
        for page in range(1, TOTAL_PAGES + 1):
            print(f"→ Page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(URL_TEMPLATE.format(page))

            # 6) Wait for product cards to appear
            try:
                wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))
            except:
                print(f"⚠️  No cards on page {page}", flush=True)
                continue

            cards = driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")
            print(f"   Found {len(cards)} cards", flush=True)

            # 7) Extract data from each card
            for card in cards:
                brand_model = card.get_attribute("data-cnstrc-item-name") or ""
                upc         = card.get_attribute("data-cnstrc-item-upc")   or ""
                mpn         = card.get_attribute("data-cnstrc-item-mpn")   or ""

                # Optional: caliber & type sub-elements
                try:
                    caliber = card.find_element(By.CSS_SELECTOR, ".cio-item-caliber").text.strip()
                except:
                    caliber = ""
                try:
                    ftype = card.find_element(By.CSS_SELECTOR, ".cio-item-type").text.strip()
                except:
                    ftype = ""

                writer.writerow({
                    "Brand/Model": brand_model,
                    "UPC": upc,
                    "MPN": mpn,
                    "Caliber": caliber,
                    "Type": ftype
                })

    driver.quit()
    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
