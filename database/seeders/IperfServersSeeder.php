<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IperfServer;
use Illuminate\Support\Facades\Storage;

class IperfServersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Đường dẫn tới file JSON
        $filePath = storage_path('app/data/1.json');

        if (file_exists($filePath)) {
            // Đọc JSON
            $jsonData = json_decode(file_get_contents($filePath), true);

            foreach ($jsonData as $record) {
                // Tách IP và port nếu có
                $ipPort = explode(':', $record['IP/HOST']);
                $ip = $ipPort[0];
                $port = isset($ipPort[1]) ? (int)$ipPort[1] : 8080; // Sử dụng null khi không có port

                // Thêm vào database
                IperfServer::firstOrCreate(
                    ['ip_address' => $ip, 'port' => $port],
                    [
                        'country_code' => $record['COUNTRY'],
                        'country_name' => $record['COUNTRY'],
                        'provider' => $record['PROVIDER'] ?? null,
                        'is_active' => true
                    ]
                );
            }
        }
    }
}