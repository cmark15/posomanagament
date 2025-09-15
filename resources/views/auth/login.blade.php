@extends('components.layout')

@section('title', 'POSO Admin Management')

@section('content')
<div class="container-fluid">
    <div class="flex justify-center items-center min-h-[calc(100vh-80px)]">
        <div class="bg-white rounded-lg shadow-md p-8 w-full max-w-md">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">LOGIN</h1>
            </div>
            @if(session('error'))
                <div class="alert alert-danger text-center">{{ session('error') }}</div>
            @endif
            <form action="{{ route('admin.login') }}" method="POST" class="space-y-6">
                @csrf
                <div class="space-y-2">
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" 
                        name="username" 
                        id="username"
                        required 
                        value="{{ old('username') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Enter your username"
                        autocomplete="username"
                    >
                </div>
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        placeholder="Enter your password"
                        autocomplete="current-password">
                </div>
                <br>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-700 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors">
                        Login
                    </button>
                </div>

                @if($errors->any()) 
                    <div class="bg-red-50 text-red-700 p-4 rounded-md border border-red-200">
                        <ul class="list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection