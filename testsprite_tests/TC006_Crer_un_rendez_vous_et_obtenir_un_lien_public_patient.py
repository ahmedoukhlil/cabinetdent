import asyncio
from playwright import async_api
from playwright.async_api import expect

async def run_test():
    pw = None
    browser = None
    context = None

    try:
        # Start a Playwright session in asynchronous mode
        pw = await async_api.async_playwright().start()

        # Launch a Chromium browser in headless mode with custom arguments
        browser = await pw.chromium.launch(
            headless=True,
            args=[
                "--window-size=1280,720",         # Set the browser window size
                "--disable-dev-shm-usage",        # Avoid using /dev/shm which can cause issues in containers
                "--ipc=host",                     # Use host-level IPC for better stability
                "--single-process"                # Run the browser in a single process mode
            ],
        )

        # Create a new browser context (like an incognito window)
        context = await browser.new_context()
        context.set_default_timeout(5000)

        # Open a new page in the browser context
        page = await context.new_page()

        # Interact with the page elements to simulate user flow
        # -> Navigate to http://localhost:8000
        await page.goto("http://localhost:8000")
        
        # -> Fill the login (identifiant) and password fields then submit the login form by clicking 'Se connecter'.
        frame = context.pages[-1]
        # Input text
        elem = frame.locator('xpath=/html/body/div/div/div[2]/form/div/div/input').nth(0)
        await asyncio.sleep(3); await elem.fill('moctar')
        
        frame = context.pages[-1]
        # Input text
        elem = frame.locator('xpath=/html/body/div/div/div[2]/form/div[2]/div/input').nth(0)
        await asyncio.sleep(3); await elem.fill('123456')
        
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/div/div/div[2]/form/button').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # -> Open the appointments management page (Gestion RDV).
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/main/div/div/div[2]/div[2]/button[3]').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # -> Open the appointments management page so I can create a new appointment (navigate to /rdv as a fallback if the UI button is not reachable).
        await page.goto("http://localhost:8000/rdv")
        
        # -> Return to the application home/dashboard so I can locate the appointments UI (use navigation since the current /rdv page is not available).
        await page.goto("http://localhost:8000")
        
        # -> Fill the login form (Identifiant and Mot de passe) and submit by clicking 'Se connecter' so we can access the staff dashboard.
        frame = context.pages[-1]
        # Input text
        elem = frame.locator('xpath=/html/body/div/div/div[2]/form/div/div/input').nth(0)
        await asyncio.sleep(3); await elem.fill('moctar')
        
        frame = context.pages[-1]
        # Input text
        elem = frame.locator('xpath=/html/body/div/div/div[2]/form/div[2]/div/input').nth(0)
        await asyncio.sleep(3); await elem.fill('123456')
        
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/div/div/div[2]/form/button').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # -> Click the 'Gestion RDV' button to open the appointments management UI so we can create a new appointment.
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/main/div/div/div[2]/div[2]/button[3]').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # -> Try an alternate path to create an appointment by opening the patient list (click 'Liste de patients').
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/main/div/div/div[2]/div[2]/button[2]').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # -> Fermer la fenêtre 'Gestion des patients' pour revenir au formulaire de création de RDV, puis rechercher et sélectionner le patient depuis le formulaire RDV.
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/main/div/div/div[5]/div/div/div/button').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # -> Type the patient name into the patient search field to locate and select the patient (start by entering 'Moctar'), then select the practitioner 'Dr. Ahmedou Khlil' (stop after selecting practitioner so the UI can update).
        frame = context.pages[-1]
        # Input text
        elem = frame.locator('xpath=/html/body/main/div/div/div[4]/div/div/div[3]/div[2]/div/div/form/div/div/div/div/div/div/input').nth(0)
        await asyncio.sleep(3); await elem.fill('Moctar')
        
        # -> Click the 'Créer le rendez-vous' button to submit the form, wait for the UI to update, then extract the appointments list rows (patient, phone, médecin, date, heure, acte, statut) and any indication of a public access token/link for the appointment.
        frame = context.pages[-1]
        # Click element
        elem = frame.locator('xpath=/html/body/main/div/div/div[4]/div/div/div[3]/div[2]/div/div/form/div[2]/button').nth(0)
        await asyncio.sleep(3); await elem.click()
        
        # --> Assertions to verify final state
        frame = context.pages[-1]
        assert await frame.locator("xpath=//*[contains(., 'Moctar')]").nth(0).is_visible(), "The appointments list should show the patient Moctar after creating the appointment"
        assert await frame.locator("xpath=//*[contains(., 'Lien public')]").nth(0).is_visible(), "The appointment entry should show a Lien public so external users can access the appointment"
        await asyncio.sleep(5)

    finally:
        if context:
            await context.close()
        if browser:
            await browser.close()
        if pw:
            await pw.stop()

asyncio.run(run_test())
    