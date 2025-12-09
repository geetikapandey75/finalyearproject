<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Trade Licence Services</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-900">

  <!-- Header -->
  <header class="bg-gray-900 text-white p-4">
    <div class="container mx-auto flex justify-between items-center">
      <h1 class="text-xl font-bold">Legal Assist</h1>
      <nav class="space-x-4">
        <a href="license.html" class="hover:underline">Home</a>
        <a href="business.html" class="hover:underline">Business Licence</a>
        <a href="food.html" class="hover:underline">Food Licence</a>
        <a href="trade.html" class="hover:underline">Trade Licence</a>
      </nav>
    </div>
  </header>

  <!-- Hero -->
  <section class="text-center py-14 bg-gradient-to-r from-blue-700 to-blue-500 text-white">
    <h2 class="text-4xl font-bold mb-4">Trade Licence Services</h2>
    <p class="text-lg mb-6">Calculate fees, check eligibility, find ward, renew reminders, and more.</p>
  </section>

  <main class="container mx-auto px-6 py-10 space-y-10">

    <!-- 1️⃣ Fee Calculator -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">1️⃣ Fee Calculator</h3>

      <div class="space-y-4">
        <select id="categoryFee" class="border p-3 w-full rounded">
          <option value="">Select Business Category</option>
          <option value="1000">Small Shop – ₹1000</option>
          <option value="2500">Medium Shop – ₹2500</option>
          <option value="5000">Large Commercial Unit – ₹5000</option>
        </select>

        <button onclick="calculateFee()" class="bg-blue-600 text-white px-6 py-3 rounded w-full">
          Calculate Fee
        </button>

        <p id="feeResult" class="text-lg font-semibold text-green-700"></p>
      </div>
    </section>


    <!-- 2️⃣ Eligibility Checker -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">2️⃣ Eligibility Checker</h3>

      <input id="ageInput" class="border p-3 w-full rounded mb-3" placeholder="Enter your age">
      <input id="shopInput" class="border p-3 w-full rounded mb-3" placeholder="Shop Size in sqft">

      <button onclick="checkEligibility()" class="bg-green-600 text-white px-6 py-3 rounded w-full">
        Check Eligibility
      </button>

      <p id="eligibilityResult" class="font-semibold mt-3"></p>
    </section>


    <!-- 3️⃣ Risk Category Checker -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">3️⃣ Risk Category Checker</h3>

      <select id="riskBusiness" class="border p-3 w-full rounded">
        <option value="">Select Business Type</option>
        <option value="Low Risk">Stationery Shop</option>
        <option value="Medium Risk">Restaurant</option>
        <option value="High Risk">Chemical Storage</option>
      </select>

      <button onclick="checkRisk()" class="bg-purple-600 text-white px-6 py-3 rounded w-full mt-3">
        Check Risk Level
      </button>

      <p id="riskResult" class="font-semibold mt-3"></p>
    </section>


    <!-- 4️⃣ Document Checklist -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">4️⃣ Document Checklist</h3>

      <button onclick="toggleDocs()" class="bg-black text-white px-6 py-3 rounded">
        Show / Hide Documents
      </button>

      <ul id="docList" class="mt-4 hidden list-disc list-inside">
        <li>ID Proof</li>
        <li>Address Proof</li>
        <li>Property Tax Receipt</li>
        <li>Shop Agreement</li>
      </ul>
    </section>


    <!-- 5️⃣ Licence Recommender -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">5️⃣ Licence Recommender</h3>

      <select id="recommender" class="border p-3 w-full rounded">
        <option value="">Select Business</option>
        <option value="Trade Licence Recommended">Salon</option>
        <option value="Trade Licence Required (High Footfall)">Restaurant</option>
        <option value="Trade Licence Mandatory">Manufacturing Unit</option>
      </select>

      <button onclick="recommendLicence()" class="bg-red-600 text-white px-6 py-3 rounded w-full mt-3">
        Recommend
      </button>

      <p id="recommendResult" class="font-semibold mt-3"></p>
    </section>


    <!-- 6️⃣ Validity Calculator -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">6️⃣ Validity Calculator</h3>

      <input type="date" id="validityDate" class="border p-3 rounded w-full mb-3">

      <button onclick="calculateValidity()" class="bg-blue-700 text-white px-6 py-3 rounded w-full">
        Calculate Validity
      </button>

      <p id="validityResult" class="mt-3 font-semibold"></p>
    </section>


    <!-- 7️⃣ Renewal Reminder -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">7️⃣ Renewal Reminder</h3>

      <input type="date" id="renewDate" class="border p-3 rounded w-full mb-3">

      <button onclick="saveReminder()" class="bg-green-700 text-white px-6 py-3 rounded w-full">
        Save Reminder
      </button>

      <p id="reminderMsg" class="mt-3 font-semibold"></p>
    </section>


    <!-- 8️⃣ Ward Finder -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">8️⃣ Ward Finder</h3>

      <input id="pincode" class="border p-3 rounded w-full mb-3" placeholder="Enter Pincode">

      <button onclick="findWard()" class="bg-indigo-600 text-white px-6 py-3 rounded w-full">
        Find Ward
      </button>

      <p id="wardResult" class="mt-3 font-semibold"></p>
    </section>


    <!-- 9️⃣ Process Timeline -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">9️⃣ Trade Licence Process Timeline</h3>

      <ol class="list-decimal list-inside space-y-2">
        <li>Application Submission</li>
        <li>Document Verification</li>
        <li>Site Inspection</li>
        <li>Approval by Authority</li>
        <li>Issuance of Trade Licence</li>
      </ol>
    </section>


    <!-- 🔟 FAQ -->
    <section class="p-6 bg-white shadow rounded-xl">
      <h3 class="text-2xl font-bold mb-4">🔟 FAQ</h3>

      <details class="mb-3">
        <summary class="font-semibold cursor-pointer">How long does it take?</summary>
        <p>Usually 7–15 working days.</p>
      </details>

      <details class="mb-3">
        <summary class="font-semibold cursor-pointer">Can I apply online?</summary>
        <p>Yes, majority of states support online applications.</p>
      </details>
    </section>

  </main>

  <!-- JS -->
  <script>
    function calculateFee() {
      let fee = document.getElementById("categoryFee").value;
      document.getElementById("feeResult").innerText = fee ? `Fee: ₹${fee}` : "";
    }

    function checkEligibility() {
      let age = document.getElementById("ageInput").value;
      let size = document.getElementById("shopInput").value;

      if (age >= 18 && size >= 100) {
        document.getElementById("eligibilityResult").innerText = "Eligible ✔";
      } else {
        document.getElementById("eligibilityResult").innerText = "Not Eligible ❌";
      }
    }

    function checkRisk() {
      let risk = document.getElementById("riskBusiness").value;
      document.getElementById("riskResult").innerText = risk;
    }

    function toggleDocs() {
      let list = document.getElementById("docList");
      list.classList.toggle("hidden");
    }

    function recommendLicence() {
      let output = document.getElementById("recommender").value;
      document.getElementById("recommendResult").innerText = output;
    }

    function calculateValidity() {
      let date = new Date(document.getElementById("validityDate").value);
      if (!date) return;

      date.setFullYear(date.getFullYear() + 1);
      document.getElementById("validityResult").innerText =
        "Valid Until: " + date.toDateString();
    }

    function saveReminder() {
      let date = document.getElementById("renewDate").value;
      localStorage.setItem("renewDate", date);
      document.getElementById("reminderMsg").innerText = "Reminder Saved ✔";
    }

    function findWard() {
      let pin = document.getElementById("pincode").value;

      let ward =
        pin.startsWith("50") ? "Ward 12" :
        pin.startsWith("51") ? "Ward 8" :
        "Unknown Area";

      document.getElementById("wardResult").innerText = ward;
    }
  </script>

</body>
</html>
