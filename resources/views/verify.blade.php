<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Nomor Sertifikasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; }
        .blue-header { background-color: #007bff; } 
        .badge-green { background-color: #198754; } 
        .badge-yellow { background-color: #ffc107; color: #000; }
        .navy-gradient { background: #003366; background: linear-gradient(to right, #021a35, #082b56, #021a35); }
    </style>
</head>
<body class="bg-[#f8f9fa] min-h-screen pb-20">

    <nav class="navy-gradient py-3 px-4 shadow-md w-full">
        <div class="max-w-[1200px] mx-auto flex justify-between items-center">
            <div class="flex items-center cursor-pointer">
                <img src="{{ asset('images/logo-kemenhub1.png') }}" alt="Logo Kemenhub" class="h-10 md:h-12 w-auto object-contain">
            </div>
            <button class="md:hidden border border-[#1e4573] rounded px-3 py-1.5 focus:outline-none">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="hidden md:flex flex-wrap justify-end gap-x-6 gap-y-2 text-[13px] text-white">
                <a href="#" class="flex gap-1 hover:text-yellow-400 transition"><span class="font-bold">Beranda</span> <span class="italic font-normal">/Home</span></a>
                <a href="#" class="flex gap-1 hover:text-yellow-400 transition"><span class="font-bold">Informasi</span> <span class="italic font-normal">/Information</span></a>
                <a href="#" class="hover:text-yellow-400 transition"><span class="font-bold">MoU Reg I/10 STCW</span></a>
                <a href="#" class="flex gap-1 hover:text-yellow-400 transition"><span class="font-bold">Verifikasi</span> <span class="italic font-normal">/Verification</span></a>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 mt-8 md:mt-12">
        
        <h1 class="text-center mb-8 text-[24px] md:text-[28px] font-extrabold text-[#111] leading-snug">
            Verifikasi Nomor Sertifikasi / <br class="block">
            <span class="italic font-extrabold">Number of Certificate Verification</span>
        </h1>

        <div class="bg-white p-4 rounded shadow-sm border border-gray-200 mb-8 max-w-2xl mx-auto flex justify-center hidden">
            <form action="{{ url()->current() }}" method="GET" class="flex gap-3 w-full">
                <input type="text" name="number" value="{{ $number ?? '6211810139011825' }}" class="flex-1 px-3 py-2 border border-gray-300 rounded text-[14px]">
                <button type="submit" class="bg-[#0d6efd] text-white px-5 py-2 rounded font-bold">Cari</button>
            </form>
        </div>

        @if(!$data)
            <p class="text-red-500 text-center text-sm font-bold bg-white p-4 rounded shadow">Sertifikat tidak ditemukan / Data not found.</p>
        @else
            <div class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                
                <div class="blue-header py-[16px] text-center">
                    <h2 class="text-white text-[22px] md:text-[24px] font-bold">
                        Data Pelaut / <span class="italic font-normal">Seafarer Data</span>
                    </h2>
                </div>

                <div class="p-6 md:p-8 flex flex-col md:flex-row gap-8 items-start">
                    
                    <div class="w-full md:w-[35%]">
                        <div class="border border-gray-200 p-6 flex flex-col items-center rounded-sm">
                            
                            <div class="w-36 h-44 mb-3 border border-gray-300 bg-[#e9ecef] flex items-center justify-center overflow-hidden">
    <img src="{{ asset('images/dummy.jpeg') }}" alt="Foto Pelaut" class="w-full h-full object-cover">
</div>
                            
                       
                            
                            <h3 class="text-center font-bold text-[18px] uppercase leading-tight mb-1 text-black">
                                {{ $data['name'] }}
                            </h3>
                            <p class="text-center font-bold text-[18px] text-black mb-6">
                                {{ substr($number, 0, 10) }}
                            </p>
                            
                            <div class="w-full flex justify-center text-center">
                                <span class="badge-yellow text-[11px] font-bold px-3 py-1 rounded-sm border border-yellow-500 shadow-sm uppercase leading-tight">
                                    {{ $data['certificate_name'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full md:w-[65%]">
                        
                        <div class="w-full text-[13.5px] mb-8">
                            <div class="flex border-b border-gray-200 py-[10px] flex-col md:flex-row gap-1 md:gap-0">
                                <div class="w-full md:w-1/2 pr-2">
                                    <div class="text-[#333]">Tempat / Tanggal Lahir</div>
                                    <div class="italic text-gray-500 text-[12px]">Place / Date of Birth</div>
                                </div>
                                <div class="w-full md:w-1/2 flex items-center text-gray-600 uppercase font-medium md:font-normal">
                                    {{ $data['birth_place'] }}, {{ strtoupper(date('d F Y', strtotime($data['birth_date']))) }}
                                </div>
                            </div>

                            <div class="flex border-b border-gray-200 py-[10px] flex-col md:flex-row gap-1 md:gap-0">
                                <div class="w-full md:w-1/2 pr-2">
                                    <div class="text-[#333]">Umur</div>
                                    <div class="italic text-gray-500 text-[12px]">Ages</div>
                                </div>
                                <div class="w-full md:w-1/2 flex items-center text-gray-600 font-medium md:font-normal">
                                    30 Tahun / Years Old
                                </div>
                            </div>

                            <div class="flex border-b border-gray-200 py-[10px] flex-col md:flex-row gap-1 md:gap-0">
                                <div class="w-full md:w-1/2 pr-2">
                                    <div class="text-[#333]">Jenis Kelamin</div>
                                    <div class="italic text-gray-500 text-[12px]">Gender</div>
                                </div>
                                <div class="w-full md:w-1/2 flex items-center text-gray-600 font-medium md:font-normal">
                                    {{ $data['gender'] }} / {{ $data['gender'] == 'Laki - laki' ? 'Male' : 'Female' }}
                                </div>
                            </div>

                            <div class="flex border-b border-gray-200 py-[10px] flex-col md:flex-row gap-1 md:gap-0">
                                <div class="w-full md:w-1/2 pr-2">
                                    <div class="text-[#333]">Status</div>
                                    <div class="italic text-gray-500 text-[12px]">Status</div>
                                </div>
                                <div class="w-full md:w-1/2 flex items-center mt-1 md:mt-0">
                                    <span class="badge-green text-white px-[8px] py-[3px] rounded-[3px] text-[10px] font-bold uppercase tracking-wide">
                                        {{ $data['status'] }} / {{ $data['status'] == 'AKTIF' ? 'ACTIVE' : $data['status'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="text-[13px] text-[#333] leading-relaxed mb-6">
                            <p class="mb-2">Dengan ini menyatakan bahwa Pelaut ini terdaftar dalam Data Induk Kepelautan dan memiliki sertifikat dasar dan keahlian tertinggi sebagai berikut :</p>
                            <p class="italic">Hereby Certify that this Seafarer is registered in Indonesian Seafarer Database and have a basic and proficiency certificates as follows:</p>
                        </div>

                        <div class="text-[13.5px] text-[#333]">
                            <p class="font-bold mb-1 uppercase">{{ $data['certificate_name'] }}</p>
                            <p class="font-bold mb-1">No. {{ $number }}</p>
                            <p class="mb-6">{{ $data['training_center'] }}</p>
                            
                           
                        </div>

                    </div>
                </div>
            </div>
        @endif
    </div>

</body>
</html>