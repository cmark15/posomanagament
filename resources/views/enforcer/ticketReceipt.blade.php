@extends('components.app')

@section('title', 'Ticket Receipt')

@section('body')
  <h1 style="text-align: center">Ticket Receipt</h1>
  
  <!-- Receipt content container -->
  <div id="receiptContent" style="border:1px solid #000; padding:10px; max-width:600px; margin: auto;">
    <div style="text-align: center;">
      <h2>Traffic Citation Ticket</h2>
      <p>Date Issued: {{ $ticket->issued_at->format('d M Y, H:i') }}</p>
    </div>
    <hr>
    <h4>Apprehending Enforcer</h4>
    <p>{{ $ticket->enforcer->fname }} {{ $ticket->enforcer->lname }}</p>

    <h4>Violator Information</h4>
    <p><strong>Name:</strong> {{ $ticket->violator->name }}</p>
    <p><strong>Address:</strong> {{ $ticket->violator->address }}</p>
    <p><strong>Birthdate:</strong> {{ $ticket->violator->birthdate }}</p>
    <p><strong>License No.:</strong> {{ $ticket->violator->license_number }}</p>

    <h4>Vehicle Information</h4>
    <p><strong>License Plate:</strong> {{ $ticket->vehicle->plate_number ?? 'N/A' }}</p>
    <p><strong>Type:</strong> {{ $ticket->vehicle->vehicle_type ?? 'N/A' }}</p>
    
    <p><strong>Owner Name:</strong> {{ $ticket->vehicle->owner_name ?? 'N/A' }}</p>

    <h4>Ticket Details</h4>
    <p><strong>Violations:</strong></p>
    @if($selectedViolations->count() > 0)
      <ul>
        @foreach ($selectedViolations as $v)
          <li>
            {{ $v->violation_name }} – Fine: ₱{{ number_format($v->fine_amount, 2) }}, Penalty: {{ $v->penalty_points }}, Category: {{ $v->category }}
          </li>
        @endforeach
      </ul>
    @else
      <p>{{ $ticket->violation_codes }}</p>
    @endif
    <p><strong>Location:</strong> {{ $ticket->location }}</p>
    <p><strong>Confiscated:</strong> {{ $ticket->confiscated }}</p>

    <h4>Login Credentials for Violator</h4>
    <p><strong>Username:</strong> {{ $credentials['username'] }}</p>
    <p><strong>Password:</strong> {{ $credentials['password'] }}</p>
    
    
  </div>

  <!-- Print Receipt Button -->
  <div style="text-align:center; margin-top:20px;">
    <button onclick="printReceipt()" class="btn btn-success">Print Receipt via Bluetooth</button>
  </div>

  <!-- Web Bluetooth integration script -->
  <script>
    async function printReceipt() {
      try {
        // Use your printer's actual UUIDs.
        const serviceUUID = '49535343-fe7d-4ae5-8fa9-9fafd205e455';
        const characteristicUUID = '49535343-8841-43f4-a8d4-ecbe34729bb3';
        
        // Request a Bluetooth device with the thermal printer service.
        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: ['49535343-fe7d-4ae5-8fa9-9fafd205e455']
        });

        // Connect to the GATT server.
        const server = await device.gatt.connect();

        // Get the printer service.
        const service = await server.getPrimaryService(serviceUUID);
        
        // Get the write characteristic.
        const characteristic = await service.getCharacteristic(characteristicUUID);

        // Build the ESC/POS command string.
        const esc = '\x1B';  // ESC
        const gs  = '\x1D';  // GS
        const newLine = '\x0A';  // New line
        let commands = '';

        // Reset printer.
        commands += esc + '@' + newLine;

        // Center header.
        commands += esc + 'a' + '\x01';
        commands += 'Traffic Citation Ticket' + newLine;
        commands += 'Date Issued: {{ $ticket->issued_at->format("d M Y, H:i") }}' + newLine;
        commands += newLine;

        // Left align for details.
        commands += esc + 'a' + '\x00';
        commands += 'Enforcer: {{ $ticket->enforcer->fname }} {{ $ticket->enforcer->lname }}' + newLine;
        commands += 'Violator: {{ $ticket->violator->name }}' + newLine;
        commands += 'Address: {{ $ticket->violator->address }}' + newLine;
        commands += 'Birthdate: {{ $ticket->violator->birthdate }}' + newLine;
        commands += 'License No.: {{ $ticket->violator->license_number }}' + newLine;
        commands += newLine;
        commands += 'Vehicle Info:' + newLine;
        commands += 'Plate: {{ $ticket->vehicle->plate_number ?? "N/A" }}' + newLine;
        commands += 'Type: {{ $ticket->vehicle->vehicle_type ?? "N/A" }}' + newLine;
       
        commands += 'Owner Name: {{ $ticket->vehicle->owner_name ?? "N/A" }}' + newLine;
        commands += newLine;
        commands += 'Violations:' + newLine;
        @if($selectedViolations->count() > 0)
          @foreach($selectedViolations as $v)
            commands += '{{ $v->violation_name }} - Fine: Php{{ number_format($v->fine_amount,2) }}, Penalty: {{ $v->penalty_points }}, Category: {{ $v->category }}' + newLine;
          @endforeach
        @else
          commands += '{{ $ticket->violation_codes }}' + newLine;
        @endif
        commands += newLine;
        commands += 'Location: {{ $ticket->location }}' + newLine;
        commands += 'Confiscated: {{ $ticket->confiscated }}' + newLine;
        commands += newLine;
        commands += 'Login Credentials:' + newLine;
        commands += 'Username: {{ $credentials["username"] }}' + newLine;
        commands += 'Password: {{ $credentials["password"] }}' + newLine;
        commands += newLine;
        
        // Feed and paper cut.
        commands += esc + 'd' + '\x03'; // Feed 3 lines.
        commands += gs + 'V' + '\x00';  // Cut paper.

        // Convert to byte array.
        let encoder = new TextEncoder();
        let data = encoder.encode(commands);

        // Write data to printer.
        await characteristic.writeValue(data);

        alert("Receipt sent to printer.");
      } catch (error) {
        console.error("Bluetooth printing error:", error);
        alert("Failed to print receipt: " + error);
      }
    }
  </script>
@endsection
