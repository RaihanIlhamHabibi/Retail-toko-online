<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AbangkuhStoreSeeder extends Seeder
{
    public function run(): void
    {
        $previousLocale = app()->getLocale();
        app()->setLocale('en');

        try {
            $context = app('aimeos.context')->get();
            $sitePath = $context->locale()->getSiteId();

            DB::table('mshop_locale_language')->where('id', 'id')->update(['status' => 1]);
            DB::table('mshop_locale_currency')->where('id', 'IDR')->update(['status' => 1]);

            $localeManager = \Aimeos\MShop::create($context, 'locale');
            $indonesianLocale = DB::table('mshop_locale')
                ->where('siteid', $sitePath)
                ->where('langid', 'id')
                ->where('currencyid', 'USD')
                ->first();

            if ($indonesianLocale) {
                DB::table('mshop_locale')
                    ->where('id', $indonesianLocale->id)
                    ->update(['pos' => 1, 'status' => 1]);
            } else {
                $localeManager->save(
                    $localeManager->create()
                        ->setLanguageId('id')
                        ->setCurrencyId('USD')
                        ->setPosition(1)
                        ->setStatus(1)
                );
            }

            DB::table('mshop_locale')
                ->where('siteid', $sitePath)
                ->where('langid', 'de')
                ->update(['status' => 0]);

            $this->addCmsPages($context, $sitePath);
            $this->addMidtransService($context, $sitePath);
        } finally {
            app()->setLocale($previousLocale);
        }
    }

    private function addCmsPages($context, string $sitePath): void
    {
        $pages = [
            '/about' => [
                'label' => 'Tentang Abangkuh',
                'id' => [
                    'title' => 'Tentang Abangkuh',
                    'content' => '<h1>Tentang Abangkuh</h1><p>Selamat datang di Abangkuh. Lengkapi halaman ini dengan profil resmi usaha sebelum toko dipublikasikan.</p>',
                ],
                'en' => [
                    'title' => 'About Abangkuh',
                    'content' => '<h1>About Abangkuh</h1><p>Welcome to Abangkuh. Add your official business profile here before publishing the store.</p>',
                ],
            ],
            '/contact' => [
                'label' => 'Kontak',
                'id' => [
                    'title' => 'Kontak Abangkuh',
                    'content' => '<h1>Kontak</h1><p>Tambahkan alamat email, nomor telepon, dan jam layanan resmi Abangkuh melalui panel admin.</p>',
                ],
                'en' => [
                    'title' => 'Contact Abangkuh',
                    'content' => '<h1>Contact</h1><p>Add Abangkuh’s official email address, phone number, and service hours in the admin panel.</p>',
                ],
            ],
            '/shipping' => [
                'label' => 'Pengiriman dan pengembalian',
                'id' => [
                    'title' => 'Pengiriman dan pengembalian',
                    'content' => '<h1>Pengiriman dan pengembalian</h1><p>Pilihan pengiriman dan biayanya ditampilkan saat checkout. Lengkapi estimasi pengiriman, wilayah layanan, dan ketentuan pengembalian sebelum toko dipublikasikan.</p>',
                ],
                'en' => [
                    'title' => 'Shipping and returns',
                    'content' => '<h1>Shipping and returns</h1><p>Shipping options and costs are shown at checkout. Add delivery estimates, service areas, and return terms before publishing the store.</p>',
                ],
            ],
            '/privacy' => [
                'label' => 'Kebijakan privasi',
                'id' => [
                    'title' => 'Kebijakan privasi (draf)',
                    'content' => '<h1>Kebijakan privasi</h1><p>Draf: jelaskan data yang dikumpulkan, tujuan dan masa penyimpanannya, pihak yang menerima data, serta kontak privasi. Tinjau sesuai praktik usaha dan aturan yang berlaku sebelum dipublikasikan.</p>',
                ],
                'en' => [
                    'title' => 'Privacy notice (draft)',
                    'content' => '<h1>Privacy notice</h1><p>Draft: describe the data collected, its purposes and retention, recipients, and a privacy contact. Review this against your business practices and applicable rules before publication.</p>',
                ],
            ],
            '/terms' => [
                'label' => 'Syarat dan ketentuan',
                'id' => [
                    'title' => 'Syarat dan ketentuan (draf)',
                    'content' => '<h1>Syarat dan ketentuan</h1><p>Draf: lengkapi ketentuan pembelian, pembayaran, pengiriman, pembatalan, dan pengembalian. Tinjau sebelum dipublikasikan.</p>',
                ],
                'en' => [
                    'title' => 'Terms and conditions (draft)',
                    'content' => '<h1>Terms and conditions</h1><p>Draft: add your purchase, payment, shipping, cancellation, and return terms. Review before publication.</p>',
                ],
            ],
        ];

        $cmsManager = \Aimeos\MShop::create($context, 'cms');
        $textManager = \Aimeos\MShop::create($context, 'text');

        foreach ($pages as $url => $page) {
            if (DB::table('mshop_cms')->where('siteid', $sitePath)->where('url', $url)->exists()) {
                continue;
            }

            $item = $cmsManager->create()
                ->setUrl($url)
                ->setLabel($page['label'])
                ->setStatus(1);

            foreach (['id', 'en'] as $position => $language) {
                $data = [
                    'cms.lists.type' => 'default',
                    'cms.lists.position' => $position,
                    'cms.lists.status' => 1,
                    'text.languageid' => $language,
                    'text.type' => 'content',
                    'text.domain' => 'cms',
                    'text.status' => 1,
                    'text.label' => $page[$language]['title'],
                    'text.content' => $page[$language]['content'],
                ];
                $listData = $data;
                $textData = $data;
                $listItem = $cmsManager->createListItem()->setPosition($position)->fromArray($listData);
                $textItem = $textManager->create()->fromArray($textData);

                $item->addListItem('text', $listItem, $textItem);
            }

            $cmsManager->save($item);
        }
    }

    private function addMidtransService($context, string $sitePath): void
    {
        if (DB::table('mshop_service')->where('siteid', $sitePath)->where('code', 'midtrans')->exists()) {
            return;
        }

        $manager = \Aimeos\MShop::create($context, 'service');
        $manager->save(
            $manager->create()
                ->setType('payment')
                ->setCode('midtrans')
                ->setProvider('Midtrans')
                ->setLabel('Midtrans Sandbox (IDR)')
                ->setStatus(0)
        );
    }
}
