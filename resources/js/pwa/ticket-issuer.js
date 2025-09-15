document.addEventListener("DOMContentLoaded", async function () {
    // Initialize QR Scanner
    const qrScanner = new Html5QrcodeScanner("qr-reader", { 
        fps: 10,
        qrbox: 250,
        rememberLastUsedCamera: true
    });

    // Scan QR Code
    document.getElementById("scanQR").addEventListener("click", function() {
        qrScanner.render(
            (qrData) => {
                fetchViolatorData(qrData);
                qrScanner.clear();
            },
            (error) => {
                console.error("QR Scan Error:", error);
            }
        );
    });

    // Form Submission with Offline Support
    document.getElementById("ticket-form").addEventListener("submit", async function (event) {
        event.preventDefault();
        
        const formData = new FormData(this);
        const violations = Array.from(document.querySelectorAll('input[name="violations[]"]:checked'))
            .map(el => el.value);

        const ticketData = {
            violator_id: formData.get('violator_id'),
            name: formData.get('name'),
            birthdate: formData.get('birthdate'),
            license_number: formData.get('license_number'),
            plate_number: formData.get('plate_number'),
            address: formData.get('address'),
            vehicle_owner: formData.get('vehicle_owner'),
            license_confiscated: formData.get('license_confiscated'),
            violations: violations,
            enforcer_id: formData.get('enforcer_id'),
            location: await getLocation(),
            timestamp: new Date().toISOString()
        };

        if (navigator.onLine) {
            await submitTicketOnline(ticketData);
        } else {
            await storeTicketOffline(ticketData);
        }
    });
});

// Get current location
async function getLocation() {
    return new Promise((resolve) => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    resolve({
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    });
                },
                (error) => {
                    console.error("Geolocation error:", error);
                    resolve(null);
                }
            );
        } else {
            resolve(null);
        }
    });
}

async function fetchViolatorData(identifier) {
    try {
        const response = await fetch(`/api/violators/${identifier}`);
        const data = await response.json();
        
        document.getElementById('violator_id').value = data.id;
        document.getElementById('full_name').value = data.full_name;
        document.getElementById('license_number').value = data.license_number;
        document.getElementById('vehicle_plate').value = data.vehicle.plate_number;
    } catch (error) {
        console.error("Error fetching violator data:", error);
    }
}

// Import IndexedDB helper
import { openDB } from 'idb';

// Enhanced Printing Function
async function printTicket(ticket) {
    if (!navigator.bluetooth) {
        return alert("Bluetooth not supported in this browser");
    }

    try {
        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: ['generic_access']
        });

        const server = await device.gatt.connect();
        const service = await server.getPrimaryService('generic_access');
        const characteristic = await service.getCharacteristic('device_name');

        const ticketContent = `
            SAN CARLOS CITY TRAFFIC TICKET
            ==============================
            Ticket No: ${ticket.ticket_number}
            Date: ${new Date(ticket.timestamp).toLocaleString()}
            
            VIOLATOR INFORMATION:
            Name: ${ticket.violator.full_name}
            License: ${ticket.violator.license_number}
            
            VEHICLE:
            Plate: ${ticket.vehicle_plate}
            
            VIOLATIONS:
            ${ticket.violations.map(v => `- ${v.name} (₱${v.fine_amount})`).join('\n')}
            
            TOTAL FINE: ₱${ticket.violations.reduce((sum, v) => sum + v.fine_amount, 0)}
            
            ISSUED BY:
            ${ticket.enforcer.name} (${ticket.enforcer.badge_number})
            
            LOCATION:
            ${ticket.location}
        `;

        const encoder = new TextEncoder();
        await characteristic.writeValue(encoder.encode(ticketContent));
        alert('Ticket printed successfully!');
    } catch (error) {
        console.error('Printing error:', error);
        alert('Printing failed. Please try again or use another printer.');
    }
}

// Online Submission
async function submitTicketOnline(ticketData) {
    try {
        const response = await fetch('/api/tickets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify(ticketData)
        });

        const result = await response.json();
        if (response.ok) {
            await printTicket(result.ticket);
            return true;
        }
        throw new Error(result.message || 'Failed to submit ticket');
    } catch (error) {
        console.error('Submission error:', error);
        await storeTicketOffline(ticketData);
        return false;
    }
}
