<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Penetapan Supplier</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; text-transform: uppercase; font-size: 20px; }
        .header p { margin: 5px 0; font-size: 12px; }
        .content { margin-top: 20px; }
        .content h2 { text-align: center; text-decoration: underline; font-size: 16px; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .details table { width: 100%; border-collapse: collapse; }
        .details table td { padding: 5px 0; vertical-align: top; }
        .details table td:first-child { width: 150px; }
        .footer { margin-top: 50px; }
        .signature { float: right; width: 250px; text-align: center; }
        .signature-space { height: 80px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PENGELOLA PENGADAAN BARANG & JASA</h1>
        <p>Jl. Jenderal Urip Sumoharjo No. 116, Jebres, Kota Surakarta</p>
        <p>Telp: (021) 12345678 | Email: admin@wise.com</p>
    </div>

    <div class="content">
        <h2>SURAT PENETAPAN SELEKSI SUPPLIER</h2>
        
        <p style="text-align: justify;">Berdasarkan hasil penilaian seleksi yang dilakukan pada tanggal {{ \Carbon\Carbon::parse($selection->tanggal)->format('d-m-Y') }}, dengan ini kami menetapkan bahwa:</p>

        <div class="details">
            <table>
                <tr>
                    <td>Nama Perusahaan</td>
                    <td>: <strong>{{ $selection->supplier->nama_perusahaan }}</strong></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: {{ $selection->supplier->alamat_perusahaan }}</td>
                </tr>
                <tr>
                    <td>Total Skor Penilaian</td>
                    <td>: {{ $selection->total_nilai }}</td>
                </tr>
                <tr>
                    <td>Status Hasil Seleksi</td>
                    <td>: <span style="color: black; font-weight: bold;">{{ $selection->status_seleksi }}</span></td>
                </tr>
            </table>
        </div>

        <p style="text-align: justify;">Atas hasil tersebut, perusahaan Saudara dinyatakan memenuhi kualifikasi dan lolos dari tahap seleksi supplier kami. Selanjutnya, perusahaan Saudara akan diarahkan untuk mengikuti tahap klasifikasi</p>

        <p style="text-align: justify;">Demikian surat penetapan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
    </div>

    <div class="footer">
        <div class="signature">
            <p>Surakarta, {{ \Carbon\Carbon::now()->format('d-m-Y') }}</p>
            <p>Admin,</p>
            <div class="signature-space"></div>
            <p><strong>( ____________________ )</strong></p>
        </div>
    </div>
</body>
</html>
