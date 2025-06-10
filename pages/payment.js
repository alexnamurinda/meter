document.addEventListener("DOMContentLoaded", function () {
    const rechargeForm = document.getElementById("recharge-form");
    const paymentMethod = document.getElementById("payment-method");
    const paymentDetails = document.getElementById("payment-details");
    const rechargeMessage = document.getElementById("recharge-message");
    const rechargeAmountField = document.getElementById("recharge-amount");

    // Function to calculate kWh
    function calculateKWh(amount) {
        return (amount / 800).toFixed(2); // Each 800 UGX is 1 kWh
    }

    // Handle Package Selection
    document.querySelectorAll(".package-btn").forEach(button => {
        button.addEventListener("click", function () {
            const amount = this.dataset.amount;
            const kwh = this.dataset.kwh;

            rechargeAmountField.value = amount;
            rechargeAmountField.disabled = false;

            rechargeMessage.textContent = `You selected ${kwh} kWh for UGX ${amount}. You can edit the amount if needed.`;
            paymentDetails.innerHTML = "";
        });
    });

    // Update kWh when user enters amount manually
    rechargeAmountField.addEventListener("input", function () {
        const amount = parseFloat(rechargeAmountField.value);
        if (!isNaN(amount) && amount > 0) {
            const kwh = calculateKWh(amount);
            rechargeMessage.textContent = `You will receive approximately ${kwh} kWh for UGX ${amount}.`;
        } else {
            rechargeMessage.textContent = "";
        }
    });

    // Handle Payment Method Selection
    paymentMethod.addEventListener("change", function () {
        const method = this.value;
        paymentDetails.innerHTML = "";

        if (method === "mtn" || method === "airtel") {
            paymentDetails.innerHTML = `
                <label for="mobile-number">Enter Mobile Number:</label>
                <input type="tel" id="mobile-number" placeholder="e.g., 2567XXXXXXXX" required>
            `;
        } else if (method === "card") {
            paymentDetails.innerHTML = `
                <label for="card-number">Enter Card Number:</label>
                <input type="text" id="card-number" placeholder="e.g., 1234 5678 9012 3456" required>
                <label for="expiry-date">Expiry Date:</label>
                <input type="month" id="expiry-date" required>
                <label for="cvv">CVV:</label>
                <input type="number" id="cvv" placeholder="e.g., 123" required>
            `;
        }
    });

    // Handle Form Submission
    rechargeForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const amount = rechargeAmountField.value.trim();
        const method = paymentMethod.value;
        const kwh = calculateKWh(amount);

        if (!amount || isNaN(amount) || amount <= 0) {
            rechargeMessage.textContent = "Please enter a valid amount.";
            return;
        }

        if (!method) {
            rechargeMessage.textContent = "Please select a payment method.";
            return;
        }

        // Collect payment details based on method
        let paymentData = {};
        if (method === "mtn" || method === "airtel") {
            paymentData.mobile_number = document.getElementById("mobile-number").value;
        } else if (method === "card") {
            paymentData.card_number = document.getElementById("card-number").value;
            paymentData.expiry_date = document.getElementById("expiry-date").value;
            paymentData.cvv = document.getElementById("cvv").value;
        }

        // Send data to the server via AJAX
        fetch("process_recharge.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                amount: amount,
                kwh: kwh,
                method: method,
                paymentData: paymentData,
            }),
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.reload) {
                    location.reload();
                }
            })
            .catch(error => {
                console.error("Error:", error);
            });
    });
});
