<x-layout>
    <x-slot name="title">Edit Violation</x-slot>

    <div class="container mt-4">
        <h2>Edit Violation</h2>
        <form action="{{ route('violation.update', $violation->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="violation_code" class="form-label">Violation Code</label>
                <input type="text" class="form-control" id="violation_code" name="violation_code" 
                       value="{{ old('violation_code', $violation->violation_code) }}" required>
            </div>
            <div class="mb-3">
                <label for="violation_name" class="form-label">Violation Name</label>
                <input type="text" class="form-control" id="violation_name" name="violation_name" 
                       value="{{ old('violation_name', $violation->violation_name) }}" required>
            </div>
            <div class="mb-3">
                <label for="fine_amount" class="form-label">Fine Amount (₱)</label>
                <input type="number" step="0.01" class="form-control" id="fine_amount" name="fine_amount" 
                       value="{{ old('fine_amount', $violation->fine_amount) }}" required>
            </div>
            <div class="mb-3">
                <label for="penalty_points" class="form-label">Penalty Points</label>
                <input type="number" class="form-control" id="penalty_points" name="penalty_points" 
                       value="{{ old('penalty_points', $violation->penalty_points) }}" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Category</label>
                <select class="form-select" id="category" name="category" required>
                    <option value="Moving Violations" {{ $violation->category == 'Moving Violations' ? 'selected' : '' }}>Moving Violations</option>
                    <option value="Non-Moving Violations" {{ $violation->category == 'Non-Moving Violations' ? 'selected' : '' }}>Non-Moving Violations</option>
                    <option value="Safety Violations" {{ $violation->category == 'Safety Violations' ? 'selected' : '' }}>Safety Violations</option>
                    <option value="Parking Violations" {{ $violation->category == 'Parking Violations' ? 'selected' : '' }}>Parking Violations</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $violation->description) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Update Violation</button>
            <a href="{{ route('violation.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</x-layout>
