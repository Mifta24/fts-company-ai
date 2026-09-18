<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * FTS's own company website content. The AI Staff answers only from what is
 * seeded here (and whatever the team later edits in the admin panel).
 *
 * Source of truth: the official company site https://fts-tech.co.id (home,
 * /about, /services pages, /projects/all), plus the AI Website product that
 * FTS is launching. English copy follows the official site; Indonesian and
 * Japanese are translations of it.
 */
class FtsCompanySeeder extends Seeder
{
    private const PORTFOLIO_IMAGES = 'https://fts-tech.co.id/images/portfolio/';

    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'admin@fts-tech.test'],
            [
                'name' => 'FTS Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $company = Company::updateOrCreate(
            ['slug' => 'fts'],
            [
                'name' => 'FTS',
                'ai_staff_name' => 'Aya',
                'tagline' => 'Kualitas Jepang, keunggulan Indonesia.',
                'description' => 'Fujiyama Technology Solutions (FTS) adalah perusahaan IT berakar Jepang di Indonesia. Kami membantu bisnis di Indonesia dan Jepang lewat pengembangan website, AI automation, pengembangan AI agent, offshore development, dan AI Website — website dengan AI Staff yang bekerja di dalamnya — untuk menghasilkan inquiry yang terukur.',
                'translations' => [
                    'en' => [
                        'tagline' => 'Japanese quality, Indonesian excellence.',
                        'description' => 'Fujiyama Technology Solutions (FTS) is a Japanese-rooted IT company in Indonesia. We help businesses in Indonesia and Japan with web development, AI automation, AI agent development, offshore development, and the AI Website — a website with an AI Staff member working inside it — to turn digital initiatives into measurable inquiries.',
                    ],
                    'ja' => [
                        'tagline' => '日本の品質、インドネシアの力。',
                        'description' => 'Fujiyama Technology Solutions（FTS）は、インドネシアに拠点を置く日本発のIT企業です。ウェブ開発、AI自動化、AIエージェント開発、オフショア開発、そしてAIスタッフが中で働く「AIウェブサイト」を通じて、インドネシアと日本のビジネスが成果につながるお問い合わせを獲得できるよう支援しています。',
                    ],
                ],
                'address' => 'Neo Soho Mall, Jl. Let. Jend. S. Parman Kav. 28 Unit 2011, Tanjung Duren Selatan, Grogol Petamburan, 11470',
                'city' => 'Jakarta Barat',
                'country' => 'Indonesia',
                'phone' => '+62 895 2933 6179',
                'email' => 'info@fts-tech.co.id',
                'website_url' => 'https://fts-tech.co.id',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'default_locale' => 'id',
                'public_status' => 'published',
            ]
        );

        $company->users()->syncWithoutDetaching([
            $owner->id => ['role' => 'owner', 'status' => 'active'],
        ]);

        $services = [];
        foreach ($this->services() as $sort => $definition) {
            $services[$definition['slug']] = $company->services()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'sort_order' => $sort, 'is_active' => true]
            );
        }

        foreach ($this->projects() as $sort => $definition) {
            $serviceSlug = $definition['service'];
            unset($definition['service']);

            $company->projects()->updateOrCreate(
                ['slug' => $definition['slug']],
                [...$definition, 'service_id' => $services[$serviceSlug]->id, 'sort_order' => $sort, 'is_active' => true]
            );
        }

        foreach ($this->knowledge() as $sort => $definition) {
            $company->knowledgeItems()->updateOrCreate(
                ['title' => $definition['title']],
                [...$definition, 'sort_order' => $sort, 'is_active' => true]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        return [
            [
                'slug' => 'ai-website',
                'category' => 'ai',
                'is_featured' => true,
                'name' => 'AI Website',
                'summary' => 'Website dengan AI Staff yang melayani pelanggan, menampilkan data nyata, dan menyelesaikan tugas — 24 jam.',
                'description' => 'AI Website bukan sekadar video atau chatbot tanya-jawab. AI Staff bekerja di dalam website Anda: memakai knowledge base perusahaan dan data bisnis yang nyata (produk, kamar, harga, ketersediaan, portofolio), menampilkan kartu dan foto langsung di percakapan, mencatat reservasi atau permintaan konsultasi, dan menyerahkan percakapan ke tim Anda saat dibutuhkan. Ke depan, AI Website dirancang untuk terhubung dengan website lain dan platform yang lebih besar. Website FTS yang sedang Anda buka ini adalah contohnya.',
                'features' => [
                    'AI Staff dengan nama dan peran untuk bisnis Anda',
                    'Menjawab hanya dari knowledge base resmi — tidak menebak',
                    'Menampilkan produk, kamar, harga, dan foto dari database',
                    'Mencatat reservasi, konsultasi, atau permintaan penawaran',
                    'Serah terima ke staf manusia dengan ringkasan percakapan',
                    'Tiga bahasa: Indonesia, Inggris, Jepang',
                    'Dashboard admin untuk data, lead, dan percakapan',
                ],
                'ideal_for' => ['Hotel dan resort', 'Restoran dan kafe', 'Klinik dan layanan jasa', 'Perusahaan yang ingin website-nya melayani calon pelanggan'],
                'pricing_model' => 'quotation',
                'starting_price' => null,
                'price_note' => 'Harga AI Website disesuaikan dengan ruang lingkup: jumlah data, fitur (misalnya reservasi), bahasa, dan integrasi. Jadwalkan konsultasi gratis untuk mendapatkan penawaran.',
                'translations' => [
                    'en' => [
                        'name' => 'AI Website',
                        'summary' => 'A website with an AI Staff member who serves customers, shows real data and gets tasks done — 24/7.',
                        'description' => 'An AI Website is not just a video or a Q&A chatbot. The AI Staff works inside your website: it uses your company knowledge base and real business data (products, rooms, prices, availability, portfolio), shows cards and photos right in the conversation, records reservations or consultation requests, and hands the conversation to your team when needed. It is designed to connect with other websites and, later, a larger platform. The FTS website you are on right now is one.',
                        'features' => [
                            'An AI Staff member with a name and role for your business',
                            'Answers only from your approved knowledge base — never guesses',
                            'Shows products, rooms, prices and photos from your database',
                            'Records reservations, consultations or quotation requests',
                            'Hands over to a human with a conversation summary',
                            'Three languages: Indonesian, English, Japanese',
                            'Admin dashboard for data, leads and conversations',
                        ],
                        'ideal_for' => ['Hotels and resorts', 'Restaurants and cafés', 'Clinics and service businesses', 'Companies that want their website to serve prospects'],
                        'price_note' => 'AI Website pricing depends on scope: amount of data, features (e.g. reservations), languages and integrations. Book a free consultation to get a quotation.',
                    ],
                    'ja' => [
                        'name' => 'AIウェブサイト',
                        'summary' => 'お客様対応、実データの表示、業務処理まで24時間こなすAIスタッフがいるウェブサイト。',
                        'description' => 'AIウェブサイトは、動画や一問一答のチャットボットではありません。AIスタッフがウェブサイトの中で働き、会社のナレッジベースと実際のビジネスデータ（商品、客室、料金、空室状況、実績）を使ってお客様に対応します。会話の中でカードや写真を表示し、予約や相談のリクエストを記録し、必要に応じてスタッフへ引き継ぎます。将来的には他のウェブサイトや大規模プラットフォームとの連携も想定しています。今ご覧のFTSのウェブサイトがその実例です。',
                        'features' => [
                            '御社専用の名前と役割を持つAIスタッフ',
                            '承認済みナレッジベースのみから回答（推測しません）',
                            '商品・客室・料金・写真をデータベースから表示',
                            '予約・相談・見積りリクエストを記録',
                            '会話の要約を添えてスタッフへ引き継ぎ',
                            'インドネシア語・英語・日本語の3言語対応',
                            'データ・リード・会話を管理できる管理画面',
                        ],
                        'ideal_for' => ['ホテル・リゾート', 'レストラン・カフェ', 'クリニック・サービス業', '見込み客対応をウェブサイトに任せたい企業'],
                        'price_note' => 'AIウェブサイトの料金は、データ量、機能（予約など）、言語数、連携内容によって異なります。無料相談にてお見積りいたします。',
                    ],
                ],
            ],
            [
                'slug' => 'web-development',
                'category' => 'development',
                'is_featured' => false,
                'name' => 'Pengembangan Website',
                'summary' => 'Website dan aplikasi web yang cepat, SEO-ready, dan dirancang untuk menghasilkan lead — dengan paket bulanan terjangkau.',
                'description' => 'Kami membangun website dan aplikasi web untuk bisnis di Indonesia, dari company profile dan landing page hingga aplikasi full-stack dan portal. Setiap website cepat, mobile-first, berstruktur SEO, terintegrasi WhatsApp, dan dioptimalkan untuk konversi. Alur kerja: sesi discovery dan perencanaan konten → wireframe, desain UI, dan penyesuaian brand → development, QA, dan setup SEO → peluncuran, analytics, dan optimasi berkelanjutan.',
                'features' => [
                    'Struktur website SEO-friendly',
                    'Integrasi WhatsApp untuk menangkap lead',
                    'Desain responsif, mobile-first',
                    'Performa & Core Web Vitals',
                    'Setup analytics dan conversion tracking',
                    'Dukungan konten SEO dan digital marketing',
                ],
                'ideal_for' => ['UMKM dan startup yang butuh kehadiran online profesional', 'Restoran, salon, dan bisnis jasa yang mengandalkan lead WhatsApp', 'Perusahaan yang butuh aplikasi web atau portal khusus', 'Brand yang ingin konten berbasis SEO'],
                'pricing_model' => 'subscription',
                'starting_price' => 700000,
                'price_unit' => '/ bulan',
                'price_note' => 'Desain & pembuatan website GRATIS — Anda cukup membayar biaya server bulanan. Kontrak 36 bulan, 6 bulan terakhir gratis. Lebih dari 15 halaman: penawaran harga sesuai scope, fitur, dan timeline.',
                'pricing_tiers' => [
                    ['name' => 'Hingga 5 halaman', 'price' => 700000, 'unit' => '/ bulan'],
                    ['name' => 'Hingga 10 halaman', 'price' => 1200000, 'unit' => '/ bulan'],
                    ['name' => 'Hingga 15 halaman', 'price' => 1600000, 'unit' => '/ bulan'],
                ],
                'translations' => [
                    'en' => [
                        'name' => 'Web Development',
                        'summary' => 'Fast, SEO-ready websites and web applications built to generate leads — on affordable monthly plans.',
                        'description' => 'We build websites and web applications for businesses in Indonesia, from company profiles and landing pages to full-stack apps and portals. Every site is fast, mobile-first, SEO-structured, WhatsApp-integrated, and optimized for conversion. Workflow: discovery session and content planning → wireframes, UI design and brand alignment → development, QA and SEO setup → launch, analytics and ongoing optimization.',
                        'features' => ['SEO-friendly website structure', 'WhatsApp integration for leads', 'Responsive, mobile-first design', 'Performance & Core Web Vitals', 'Analytics and conversion tracking setup', 'SEO content and digital marketing support'],
                        'ideal_for' => ['SMEs and startups that need a professional online presence', 'Restaurants, salons and service businesses that rely on WhatsApp leads', 'Enterprises looking for custom web applications and portals', 'Brands that want SEO-driven content'],
                        'price_unit' => '/ month',
                        'price_note' => 'Website design & development is FREE — you pay only the monthly server fee. 36-month contract, last 6 months free. More than 15 pages: quotation based on scope, features and timeline. (Approx. USD: $42.42 / $72.73 / $96.97 per month.)',
                    ],
                    'ja' => [
                        'name' => 'ウェブ開発',
                        'summary' => '高速・SEO対応で、お問い合わせにつながるウェブサイトとウェブアプリ。手頃な月額プランで。',
                        'description' => 'コーポレートサイトやランディングページから、フルスタックのウェブアプリ・ポータルまで、インドネシアのビジネス向けに開発します。すべてのサイトは高速・モバイルファースト・SEO構造・WhatsApp連携で、コンバージョンを重視しています。流れ：ヒアリングとコンテンツ企画 → ワイヤーフレーム・UIデザイン・ブランド調整 → 開発・QA・SEO設定 → 公開・分析・継続的な改善。',
                        'features' => ['SEOに強いサイト構造', 'WhatsApp連携でお問い合わせを獲得', 'レスポンシブ・モバイルファースト設計', '表示速度とCore Web Vitalsの最適化', '分析・コンバージョン計測の設定', 'SEOコンテンツとデジタルマーケティング支援'],
                        'ideal_for' => ['プロフェッショナルなサイトが必要な中小企業・スタートアップ', 'WhatsAppからの問い合わせが重要な飲食店・サロン・サービス業', '独自のウェブアプリやポータルが必要な企業', 'SEOを軸にコンテンツを展開したいブランド'],
                        'price_unit' => '/ 月',
                        'price_note' => 'ウェブサイトのデザイン・制作費は無料で、月額のサーバー費用のみいただきます。36ヶ月契約で最後の6ヶ月は無料。15ページを超える場合は、範囲・機能・スケジュールに応じてお見積りします。',
                    ],
                ],
            ],
            [
                'slug' => 'ai-automation',
                'category' => 'automation',
                'is_featured' => false,
                'name' => 'AI Automation & Pengembangan AI Agent',
                'summary' => 'Otomasi penjualan, dukungan pelanggan, dan operasional dengan AI automation dan AI agent yang disesuaikan dengan alur kerja Anda.',
                'description' => 'Kami mengidentifikasi tugas berulang di penjualan, marketing, HR, dan operasional, lalu menerapkan otomasi untuk menekan biaya dan mempercepat respons. AI agent kami dirancang sesuai logika bisnis Anda untuk menangani pertanyaan pelanggan, kualifikasi lead, dan operasional internal. Roadmap: sesi discovery untuk memetakan proses → prototipe dan pilot untuk memvalidasi ROI dengan cepat → peluncuran produksi dengan monitoring dan optimasi berkelanjutan. Bisa dimulai dari satu alur kerja kecil.',
                'features' => [
                    'Kualifikasi lead dan dukungan pelanggan via WhatsApp',
                    'Asisten knowledge internal untuk keputusan lebih cepat',
                    'Laporan dan ringkasan data otomatis',
                    'Orkestrasi alur kerja antar tools dan API',
                    'Desain AI agent khusus & deployment multi-channel',
                    'Monitoring, prompt tuning, dan optimasi berkelanjutan',
                ],
                'ideal_for' => ['Tim sales dan customer support', 'Bisnis yang ingin tumbuh tanpa menambah banyak karyawan'],
                'pricing_model' => 'quotation',
                'starting_price' => null,
                'price_note' => 'Harga tergantung alur kerja yang diotomasi. Kami sering memulai dari satu alur kerja, memvalidasi ROI, lalu memperluasnya.',
                'translations' => [
                    'en' => [
                        'name' => 'AI Automation & AI Agent Development',
                        'summary' => 'Automate sales, support and operations with AI automation and custom AI agents tailored to your workflows.',
                        'description' => 'We identify repetitive tasks across sales, marketing, HR and operations, then deploy automation to reduce costs and improve response times. Our AI agents are designed around your business logic to handle customer inquiries, lead qualification and internal operations. Roadmap: a discovery session to map processes → a prototype and pilot to validate ROI quickly → production rollout with monitoring and continuous optimization. You can start with one small workflow.',
                        'features' => ['Lead qualification and WhatsApp customer support', 'Internal knowledge assistants for faster decisions', 'Automated reporting and data summarization', 'Workflow orchestration across tools and APIs', 'Custom AI agent design & multi-channel deployment', 'Monitoring, prompt tuning and continuous optimization'],
                        'ideal_for' => ['Sales and customer support teams', 'Businesses that want to scale without adding headcount'],
                        'price_note' => 'Pricing depends on the workflow being automated. We often start with one workflow, validate ROI, and expand from there.',
                    ],
                    'ja' => [
                        'name' => 'AI自動化・AIエージェント開発',
                        'summary' => '営業・サポート・業務を、御社のワークフローに合わせたAI自動化とAIエージェントで効率化。',
                        'description' => '営業、マーケティング、人事、業務の中の繰り返し作業を洗い出し、自動化によってコスト削減と対応スピード向上を実現します。AIエージェントは御社のビジネスロジックに合わせて設計し、お客様対応、見込み客の選別、社内業務を担います。進め方：業務を整理するディスカバリー → ROIを素早く検証するプロトタイプ・パイロット → 監視と継続改善を伴う本番導入。小さなワークフローひとつから始められます。',
                        'features' => ['見込み客の選別とWhatsAppでのお客様対応', '意思決定を早める社内ナレッジアシスタント', 'レポート作成・データ要約の自動化', 'ツールやAPIをまたぐワークフロー連携', 'AIエージェントの個別設計とマルチチャネル展開', '監視・プロンプト調整・継続的な最適化'],
                        'ideal_for' => ['営業・カスタマーサポートチーム', '人員を増やさずに事業を伸ばしたい企業'],
                        'price_note' => '料金は自動化する業務内容によって異なります。まずひとつのワークフローでROIを検証し、段階的に広げることが多いです。',
                    ],
                ],
            ],
            [
                'slug' => 'offshore-development',
                'category' => 'development',
                'is_featured' => false,
                'name' => 'Offshore Development Indonesia',
                'summary' => 'Tim developer khusus di Indonesia untuk perusahaan Jepang, dengan standar kualitas Jepang dan koordinasi dua bahasa.',
                'description' => 'Kami membentuk tim khusus di Indonesia yang bekerja langsung dengan product owner Anda, dengan standar delivery Jepang dan transparansi penuh. Tim terdiri dari developer, QA, dan project manager, didukung koordinator dwibahasa Jepang. Setiap sprint melalui code review dan automated testing, dengan kontrol akses aman, alur kerja sesuai NDA, serta QA sign-off sebelum rilis. Komunikasi melalui sync mingguan, dashboard proyek bersama, dan laporan dua bahasa.',
                'features' => [
                    'Tim developer khusus (developer, QA, PM)',
                    'Proses QA berstandar Jepang',
                    'Koordinasi proyek dwibahasa',
                    'Skala tim fleksibel untuk proyek jangka panjang',
                    'Zona waktu selaras dengan Jepang dan Asia Tenggara',
                    'Alur kerja aman dan sesuai NDA',
                ],
                'ideal_for' => ['Perusahaan Jepang yang membutuhkan offshore development di Indonesia'],
                'pricing_model' => 'quotation',
                'starting_price' => null,
                'price_note' => 'Pilihan kerja sama: model tim khusus (dedicated team) untuk pengembangan berkelanjutan, delivery berbasis proyek untuk scope yang jelas, atau retainer untuk maintenance dan optimasi. Harga sesuai struktur tim.',
                'translations' => [
                    'en' => [
                        'name' => 'Offshore Development Indonesia',
                        'summary' => 'Dedicated development teams in Indonesia for Japanese companies, with Japanese-standard quality and bilingual coordination.',
                        'description' => 'We build dedicated teams in Indonesia that collaborate directly with your product owners, maintaining Japanese delivery standards and transparency. Teams include developers, QA and project managers, backed by Japanese bilingual coordinators. Every sprint gets code reviews and automated testing, with secure access controls, NDA-compliant workflows, and QA sign-off before release. Communication runs through weekly syncs, shared project dashboards and bilingual reporting.',
                        'features' => ['Dedicated developer teams (developers, QA, PM)', 'Japanese-standard QA process', 'Bilingual project coordination', 'Flexible team scaling for long-term projects', 'Time zone alignment for Japan and Southeast Asia', 'Secure, NDA-compliant workflows'],
                        'ideal_for' => ['Japanese companies requiring offshore development in Indonesia'],
                        'price_note' => 'Engagement options: a dedicated team model for ongoing development, project-based delivery for defined scopes, or retainer support for maintenance and optimization. Pricing depends on team structure.',
                    ],
                    'ja' => [
                        'name' => 'インドネシア・オフショア開発',
                        'summary' => '日本企業向けに、インドネシアで専任開発チームを編成。日本基準の品質とバイリンガルでの進行管理。',
                        'description' => 'インドネシアに専任チームを編成し、御社のプロダクトオーナーと直接連携しながら、日本基準の品質と透明性を保って開発を進めます。チームは開発者・QA・プロジェクトマネージャーで構成され、日本語対応のコーディネーターがサポートします。スプリントごとにコードレビューと自動テストを実施し、安全なアクセス管理、NDAに準拠した運用、リリース前のQA承認を徹底しています。週次ミーティング、共有ダッシュボード、日英（日本語）のレポートで進捗を共有します。',
                        'features' => ['専任開発チーム（開発者・QA・PM）', '日本基準のQAプロセス', 'バイリンガルでのプロジェクト調整', '長期案件に合わせた柔軟なチーム拡張', '日本・東南アジアに近いタイムゾーン', 'セキュアでNDAに準拠した開発体制'],
                        'ideal_for' => ['インドネシアでのオフショア開発を検討中の日本企業'],
                        'price_note' => '継続開発向けの専任チーム型、範囲が明確な案件向けのプロジェクト型、保守・改善向けのリテイナー型からお選びいただけます。料金はチーム構成によって異なります。',
                    ],
                ],
            ],
            [
                'slug' => 'restaurant-website',
                'category' => 'development',
                'is_featured' => false,
                'name' => 'Website Restoran',
                'summary' => 'Website restoran siap WhatsApp dengan menu digital, form reservasi, dan SEO lokal — untuk mengubah pengunjung menjadi pelanggan.',
                'description' => 'Website untuk restoran dan kafe yang dirancang untuk mengisi meja: menu digital dan galeri foto, tombol pemesanan via WhatsApp, SEO lokal untuk Google Maps, serta halaman reservasi dan event. Dapat dipadukan dengan FTS Menu untuk menu QR yang selalu terbaru.',
                'features' => [
                    'Menu digital & galeri foto',
                    'Tombol pemesanan WhatsApp',
                    'SEO lokal untuk Google Maps',
                    'Halaman reservasi & event',
                ],
                'ideal_for' => ['Restoran, kafe, dan bisnis kuliner'],
                'pricing_model' => 'quotation',
                'starting_price' => null,
                'price_note' => 'Hubungi tim FTS untuk penawaran. Untuk website biasa, lihat juga paket bulanan Pengembangan Website.',
                'translations' => [
                    'en' => [
                        'name' => 'Restaurant Website Development',
                        'summary' => 'WhatsApp-ready restaurant websites with digital menus, reservation forms and local SEO — designed to convert visitors into customers.',
                        'description' => 'Websites for restaurants and cafés designed to fill tables: a digital menu and photo gallery, WhatsApp ordering buttons, local SEO for Google Maps, and reservation and event pages. Can be combined with FTS Menu for an always-up-to-date QR menu.',
                        'features' => ['Digital menu & photo gallery', 'WhatsApp ordering buttons', 'Local SEO for Google Maps', 'Reservation & event pages'],
                        'ideal_for' => ['Restaurants, cafés and food businesses'],
                        'price_note' => 'Contact the FTS team for a quotation. For standard websites, see also the Web Development monthly plans.',
                    ],
                    'ja' => [
                        'name' => 'レストラン向けウェブサイト',
                        'summary' => 'デジタルメニュー、予約フォーム、ローカルSEOを備えたWhatsApp対応のレストランサイト。来訪者をお客様に。',
                        'description' => '席を埋めることを目的に設計した飲食店向けウェブサイトです。デジタルメニューと写真ギャラリー、WhatsApp注文ボタン、Googleマップ向けのローカルSEO、予約・イベントページを備えています。FTS Menuと組み合わせて、常に最新のQRメニューも提供できます。',
                        'features' => ['デジタルメニュー・写真ギャラリー', 'WhatsApp注文ボタン', 'Googleマップ向けローカルSEO', '予約・イベントページ'],
                        'ideal_for' => ['レストラン・カフェ・飲食業'],
                        'price_note' => 'お見積りはFTSチームまでお問い合わせください。一般的なサイトはウェブ開発の月額プランもご覧ください。',
                    ],
                ],
            ],
            [
                'slug' => 'business-automation-marketing',
                'category' => 'automation',
                'is_featured' => false,
                'name' => 'Otomasi Bisnis & Digital Marketing',
                'summary' => 'Otomasi end-to-end dari penangkapan lead hingga pelacakan konversi, menghubungkan website, WhatsApp, dan CRM.',
                'description' => 'Kami membangun sistem yang menghubungkan website, WhatsApp, dan CRM Anda untuk menghasilkan hasil yang terukur: penangkapan dan nurturing lead, analytics dan conversion tracking, otomasi kampanye WhatsApp, serta dukungan digital marketing. Integrasi yang didukung antara lain WhatsApp Business, email, platform CRM, Google Workspace, Slack, ERP, POS, dan API khusus.',
                'features' => [
                    'Lead capture & nurturing',
                    'Analytics & conversion tracking',
                    'Otomasi kampanye WhatsApp',
                    'Dukungan digital marketing',
                ],
                'ideal_for' => ['Bisnis yang ingin lead dan penjualannya terukur'],
                'pricing_model' => 'quotation',
                'starting_price' => null,
                'price_note' => 'Harga sesuai sistem dan integrasi yang dibutuhkan. Hubungi tim FTS untuk konsultasi.',
                'translations' => [
                    'en' => [
                        'name' => 'Business Automation & Digital Marketing',
                        'summary' => 'End-to-end automation from lead capture to conversion tracking, connecting your website, WhatsApp and CRM.',
                        'description' => 'We build systems that connect your website, WhatsApp and CRM to generate measurable results: lead capture and nurturing, analytics and conversion tracking, WhatsApp campaign automation, and digital marketing support. Supported integrations include WhatsApp Business, email, CRM platforms, Google Workspace, Slack, ERP, POS and custom APIs.',
                        'features' => ['Lead capture & nurturing', 'Analytics & conversion tracking', 'WhatsApp campaign automation', 'Digital marketing support'],
                        'ideal_for' => ['Businesses that want measurable leads and sales'],
                        'price_note' => 'Pricing depends on the systems and integrations needed. Contact the FTS team for a consultation.',
                    ],
                    'ja' => [
                        'name' => '業務自動化・デジタルマーケティング',
                        'summary' => 'リード獲得からコンバージョン計測まで。ウェブサイト・WhatsApp・CRMをつなぐ一貫した自動化。',
                        'description' => 'ウェブサイト、WhatsApp、CRMをつなぎ、成果を測定できる仕組みを構築します。リードの獲得・育成、分析とコンバージョン計測、WhatsAppキャンペーンの自動化、デジタルマーケティング支援に対応。WhatsApp Business、メール、CRM、Google Workspace、Slack、ERP、POS、独自APIとの連携が可能です。',
                        'features' => ['リード獲得・育成', '分析・コンバージョン計測', 'WhatsAppキャンペーン自動化', 'デジタルマーケティング支援'],
                        'ideal_for' => ['問い合わせや売上を数値で管理したい企業'],
                        'price_note' => '料金は必要なシステムと連携内容によって異なります。FTSチームにご相談ください。',
                    ],
                ],
            ],
            [
                'slug' => 'fts-menu',
                'category' => 'saas',
                'is_featured' => false,
                'name' => 'FTS Menu — Menu Digital QR',
                'summary' => 'Menu digital berbasis QR untuk restoran dan kafe. Ubah harga, foto, dan status menu kapan saja dari HP.',
                'description' => 'FTS Menu adalah platform menu digital berlangganan. Setiap restoran mendapat URL menu sendiri, QR code, dan dashboard untuk mengelola kategori, harga, foto, serta status tersedia/habis tanpa mencetak ulang menu. Menu dapat ditampilkan dalam Bahasa Indonesia, Inggris, dan Jepang. Kunjungi fts-menu.fts-tech.co.id.',
                'features' => [
                    'URL menu unik dan QR code',
                    'Kelola harga, foto, dan deskripsi dari dashboard',
                    'Status tersedia / habis',
                    'Menu multibahasa (ID/EN/JP)',
                    'Tema warna dan statistik (paket Business ke atas)',
                ],
                'ideal_for' => ['Kafe kecil dan menengah', 'Restoran di daerah wisata', 'Restoran yang sering mengganti menu atau harga'],
                'pricing_model' => 'subscription',
                'starting_price' => 49000,
                'price_unit' => '/ bulan',
                'price_note' => 'Tersedia paket Free (Rp0) untuk mencoba. Paket berbayar mulai Rp49.000 per bulan. Layanan setup data menu juga tersedia.',
                'pricing_tiers' => [
                    ['name' => 'Free', 'price' => 0, 'unit' => '/ bulan'],
                    ['name' => 'Starter', 'price' => 49000, 'unit' => '/ bulan'],
                    ['name' => 'Business', 'price' => 99000, 'unit' => '/ bulan'],
                    ['name' => 'Pro', 'price' => 149000, 'unit' => '/ bulan'],
                ],
                'translations' => [
                    'en' => [
                        'name' => 'FTS Menu — QR Digital Menu',
                        'summary' => 'A QR-based digital menu for restaurants and cafés. Update prices, photos and availability any time from your phone.',
                        'description' => 'FTS Menu is a subscription digital-menu platform. Each restaurant gets its own menu URL, a QR code and a dashboard to manage categories, prices, photos and sold-out status without reprinting. Menus can be shown in Indonesian, English and Japanese. Visit fts-menu.fts-tech.co.id.',
                        'features' => ['Unique menu URL and QR code', 'Manage prices, photos and descriptions from a dashboard', 'Available / sold-out status', 'Multilingual menu (ID/EN/JP)', 'Color themes and statistics (Business plan and up)'],
                        'price_unit' => '/ month',
                        'price_note' => 'A Free plan (Rp0) is available to try. Paid plans start at Rp49,000 per month. A menu data setup service is also available.',
                    ],
                    'ja' => [
                        'name' => 'FTS Menu — QRデジタルメニュー',
                        'summary' => 'レストラン・カフェ向けQRデジタルメニュー。価格・写真・提供状況をスマホからいつでも更新。',
                        'description' => 'FTS Menuはサブスクリプション型のデジタルメニューです。各店舗に専用URL、QRコード、管理画面が用意され、カテゴリ・価格・写真・売り切れ表示を印刷し直すことなく管理できます。インドネシア語・英語・日本語で表示できます。fts-menu.fts-tech.co.id をご覧ください。',
                        'features' => ['専用メニューURLとQRコード', '管理画面から価格・写真・説明を編集', '提供中／売り切れ表示', '多言語メニュー（ID/EN/JP）', 'カラーテーマと統計（Businessプラン以上）'],
                        'price_unit' => '/ 月',
                        'price_note' => 'お試し用のFreeプラン（Rp0）があります。有料プランは月額Rp49,000から。メニューデータの登録代行サービスもございます。',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function projects(): array
    {
        return [
            [
                'slug' => 'hotel-ai-website',
                'service' => 'ai-website',
                'is_featured' => true,
                'name' => 'Hotel AI Website V1',
                'client_name' => 'Demo: Samudra Bali Resort',
                'industry' => 'hospitality',
                'status' => 'demo',
                'image_url' => '/images/projects/hotel-ai-website.png',
                'tags' => ['hotel', 'ai', 'booking', 'reservasi', 'indonesia', 'ホテル', '予約'],
                'summary' => 'Resepsionis virtual untuk hotel: tamu bertanya soal kamar, melihat foto, cek harga & ketersediaan, lalu mengajukan reservasi.',
                'description' => 'AI Concierge bekerja di dalam website hotel. Tamu dapat bertanya tentang fasilitas dan kebijakan (dijawab dari knowledge base hotel), mencari kamar sesuai tanggal dan jumlah tamu, melihat foto kamar, mendapatkan harga total yang dihitung dari data ketersediaan real-time, lalu membuat permintaan reservasi. Permintaan khusus diteruskan ke staf hotel lewat dashboard admin.',
                'highlights' => [
                    'Harga dan ketersediaan kamar selalu dari database, bukan dari ingatan AI',
                    'Kartu kamar dan foto tampil langsung di percakapan',
                    'Reservasi dengan pengecekan ulang ketersediaan',
                    'Serah terima ke staf hotel dengan ringkasan',
                    'Bahasa Indonesia, Inggris, dan Jepang',
                ],
                'tech_stack' => ['Laravel', 'Self-hosted LLM', 'Tool calling', 'Tailwind CSS'],
                'translations' => [
                    'en' => [
                        'name' => 'Hotel AI Website V1',
                        'summary' => 'A virtual front desk for hotels: guests ask about rooms, view photos, check prices and availability, then request a reservation.',
                        'description' => 'The AI Concierge works inside the hotel website. Guests can ask about facilities and policies (answered from the hotel knowledge base), search rooms for their dates and party size, view room photos, get a total price calculated from real-time availability data, and submit a reservation request. Special requests are passed to hotel staff through the admin dashboard.',
                        'highlights' => ['Room prices and availability always come from the database, never the AI\'s memory', 'Room cards and photos appear right in the conversation', 'Reservations re-check availability before booking', 'Handover to hotel staff with a summary', 'Indonesian, English and Japanese'],
                    ],
                    'ja' => [
                        'name' => 'ホテルAIウェブサイト V1',
                        'summary' => 'ホテル向けバーチャルフロント：客室の質問、写真の閲覧、料金・空室確認から予約リクエストまで。',
                        'description' => 'AIコンシェルジュがホテルのウェブサイト内で働きます。ゲストは施設や規定について質問でき（ホテルのナレッジベースから回答）、日程と人数に合う客室の検索、客室写真の閲覧、リアルタイムの空室データに基づく合計料金の確認、予約リクエストの送信ができます。特別なご要望は管理画面を通じてホテルスタッフへ引き継がれます。',
                        'highlights' => ['客室の料金と空室状況は常にデータベースから取得', '客室カードと写真を会話内に表示', '予約前に空室を再確認', '要約付きでホテルスタッフへ引き継ぎ', 'インドネシア語・英語・日本語対応'],
                    ],
                ],
            ],
            [
                'slug' => 'fts-ai-website',
                'service' => 'ai-website',
                'is_featured' => true,
                'name' => 'FTS AI Website',
                'client_name' => 'FTS',
                'industry' => 'technology',
                'status' => 'live',
                'image_url' => '/images/projects/fts-ai-website.png',
                'tags' => ['ai', 'company profile', 'lead', 'indonesia', 'japan'],
                'summary' => 'Website perusahaan FTS sendiri — yang sedang Anda gunakan sekarang. AI Staff memperkenalkan FTS dan membantu calon pelanggan.',
                'description' => 'Website ini adalah contoh nyata AI Website. AI Staff FTS memperkenalkan perusahaan, menjelaskan layanan, menampilkan proyek, menjawab pertanyaan dari knowledge base, dan mencatat permintaan konsultasi, demo, atau penawaran untuk tim sales. Semua konten dapat diperbarui tim FTS dari dashboard admin.',
                'highlights' => [
                    'AI Staff memperkenalkan perusahaan dan layanan',
                    'Portofolio dan harga ditampilkan dari database',
                    'Calon pelanggan tercatat otomatis sebagai lead',
                    'Tim FTS dapat mengambil alih percakapan kapan saja',
                ],
                'tech_stack' => ['Laravel', 'Self-hosted LLM', 'Tool calling', 'Tailwind CSS'],
                'translations' => [
                    'en' => [
                        'name' => 'FTS AI Website',
                        'summary' => 'FTS\'s own company website — the one you are using right now. The AI Staff introduces FTS and guides prospects.',
                        'description' => 'This website is a live example of an AI Website. The FTS AI Staff introduces the company, explains services, shows projects, answers questions from the knowledge base, and records consultation, demo or quotation requests for the sales team. The FTS team can update all content from the admin dashboard.',
                        'highlights' => ['AI Staff introduces the company and its services', 'Portfolio and pricing come from the database', 'Prospects are captured automatically as leads', 'The FTS team can take over any conversation'],
                    ],
                    'ja' => [
                        'name' => 'FTS AIウェブサイト',
                        'summary' => '今ご覧いただいているFTSのコーポレートサイト。AIスタッフが会社を紹介し、見込み客をご案内します。',
                        'description' => 'このウェブサイト自体がAIウェブサイトの実例です。FTSのAIスタッフが会社紹介、サービス説明、実績紹介、ナレッジベースに基づく回答を行い、相談・デモ・見積りのリクエストを営業チームのために記録します。すべてのコンテンツは管理画面から更新できます。',
                        'highlights' => ['AIスタッフが会社とサービスをご紹介', '実績と料金はデータベースから表示', '見込み客を自動でリードとして記録', 'FTSチームがいつでも会話を引き継ぎ可能'],
                    ],
                ],
            ],
            $this->portfolio('edepa-world', 'EDEPA World', 'web-development', 'creative', 'edepa.jpg', ['creative agency', 'company profile', 'portfolio', 'japan', 'jepang', '日本'],
                ['Website company profile untuk EDEPA World, agensi kreatif Jepang, yang menampilkan gambaran bisnis, portofolio proyek, dan topik terbaru.',
                    'Company profile website for EDEPA World, a Japanese creative agency, presenting their business overview, project portfolio, and latest topics.',
                    '日本のクリエイティブエージェンシーEDEPA Worldのコーポレートサイト。事業概要、制作実績、最新トピックを紹介しています。'],
                client: 'EDEPA World', featured: true),
            $this->portfolio('fujiyama-biomass-energy', 'FBE - Fujiyama Biomass Energy', 'web-development', 'energy', 'fbe.jpg', ['biomass energy', 'sustainability', 'export', 'indonesia', 'corporate website'],
                ['Website korporat untuk Fujiyama Biomass Energy yang mempromosikan produk arang PKS premium, inisiatif keberlanjutan, dan kemitraan ekspor.',
                    'Corporate website for Fujiyama Biomass Energy, promoting premium PKS charcoal products, sustainability initiatives, and export partnerships.',
                    'Fujiyama Biomass Energyのコーポレートサイト。高品質なPKS炭製品、サステナビリティへの取り組み、輸出パートナーシップを紹介しています。'],
                client: 'Fujiyama Biomass Energy'),
            $this->portfolio('building-maintenance', 'Building Maintenance', 'web-development', 'facility', 'building-maintenance.jpg', ['building maintenance', 'facility management', 'company profile', 'japan', 'jepang', '日本'],
                ['Website company profile untuk perusahaan perawatan gedung asal Jepang, menonjolkan keahlian manajemen fasilitas dan layanannya.',
                    'Company profile website for a Japanese building maintenance firm, highlighting specialized facility management expertise and service offerings.',
                    '日本のビルメンテナンス会社のコーポレートサイト。施設管理の専門性とサービス内容を紹介しています。']),
            $this->portfolio('looklook', 'LookLook', 'web-development', 'tourism', 'looklook.jpg', ['travel platform', 'tourism', 'directory', 'multilingual', 'japan', 'jepang', '日本', '観光'],
                ['Platform informasi wisata untuk pengunjung Jepang: pencarian restoran, tempat wisata, info WiFi gratis, dan pencarian multibahasa per wilayah.',
                    'Travel information platform for visitors to Japan, offering restaurant search, sightseeing spots, free WiFi info, and multilingual search by region.',
                    '訪日客向けの旅行情報プラットフォーム。飲食店検索、観光スポット、無料WiFi情報、地域別の多言語検索を提供しています。'],
                client: 'LookLook'),
            $this->portfolio('minnano-kuruma', 'Minnano Kuruma', 'web-development', 'automotive', 'minnano-kuruma.jpg', ['used car buying', 'automotive', 'local business', 'landing page', 'line', 'japan', 'jepang', '日本'],
                ['Landing page lokal untuk layanan pembelian mobil bekas di area Nishitama, Jepang, dengan telepon penaksiran gratis, dukungan LINE, dan elemen kepercayaan pelanggan.',
                    'Local landing page for a used car buying service in the Nishitama area of Japan, featuring free appraisal calls, LINE support, and customer trust signals.',
                    '西多摩エリアの中古車買取サービスのランディングページ。無料査定の電話受付、LINE対応、お客様の安心につながる情報を掲載しています。'],
                client: 'Minnano Kuruma'),
            $this->portfolio('xchangepro', 'XchangePro', 'web-development', 'automotive', 'xchangepro.jpg', ['tire shop', 'automotive', 'service business', 'tokyo', 'saitama', 'japan', 'jepang', '日本'],
                ['Website bisnis untuk toko ban dan velg, menampilkan penjualan ban, pemasangan dan penggantian, serta perawatan mobil di area Tokyo/Saitama.',
                    'Business website for a tire and wheel shop, showcasing tire sales, installation and exchange services, and car maintenance in the Tokyo/Saitama area.',
                    '東京・埼玉エリアのタイヤ・ホイール専門店のサイト。タイヤ販売、取付・交換、車のメンテナンスを紹介しています。'],
                client: 'XchangePro'),
            $this->portfolio('ebila-hall', 'Ebila Hall', 'web-development', 'hospitality', 'ebilahall.jpg', ['event venue', 'booking system', 'reservasi', 'hospitality', 'shinagawa', 'japan', 'jepang', '日本', '予約'],
                ['Website booking untuk hall musik dan event serbaguna di Shinagawa, dengan reservasi online untuk pernikahan, konser, dan acara pribadi.',
                    'Booking website for a versatile music and event hall in Shinagawa Ward, featuring online reservations for weddings, concerts, and private gatherings.',
                    '品川区の多目的音楽・イベントホールの予約サイト。結婚式、コンサート、プライベートイベントのオンライン予約に対応しています。'],
                client: 'Ebila Hall', featured: true),
            $this->portfolio('san-kou-kai-hiking', 'Hiking (San Kou Kai)', 'web-development', 'community', 'hiking.jpg', ['community website', 'hiking club', 'blog', 'japan', 'jepang', '日本'],
                ['Website komunitas untuk perkumpulan pendaki Jepang, berisi laporan perjalanan, rencana pendakian, dan informasi keanggotaan untuk pendakian yang aman.',
                    'Community website for a Japanese hiking association, sharing trip reports, hiking plans, and membership information for safe mountain climbing.',
                    '日本の登山会のコミュニティサイト。山行記録、登山計画、安全な登山のための会員情報を発信しています。'],
                client: 'San Kou Kai'),
            $this->portfolio('car-repair', 'Car Repair', 'web-development', 'automotive', 'car-repair.jpg', ['auto repair', 'booking system', 'reservasi', 'automotive', 'service business', 'testimonials'],
                ['Website bisnis untuk bengkel body mobil, menampilkan layanan perbaikan, sistem booking online, dan testimoni pelanggan.',
                    'Business website for an auto body repair shop, showcasing repair services, an online booking system, and customer testimonials.',
                    '自動車板金修理工場のサイト。修理サービス、オンライン予約システム、お客様の声を掲載しています。']),
            $this->portfolio('thai-travel', 'Thai Travel', 'web-development', 'tourism', 'thai-travel.jpg', ['travel booking', 'tourism', 'transportation', 'booking system', 'thailand', 'thailand'],
                ['Platform booking perjalanan di Thailand: transfer bandara, sewa mobil pribadi, dan paket tur melalui sistem pencarian dan booking online.',
                    'Travel booking platform for Thailand, offering airport transfers, private car rentals, and tour packages through an online search and booking system.',
                    'タイの旅行予約プラットフォーム。空港送迎、専用車チャーター、ツアーパッケージをオンラインで検索・予約できます。'],
                client: 'Thai Travel'),
            [
                ...$this->portfolio('fts-menu-platform', 'FTS Menu', 'fts-menu', 'restaurant', 'fts-menu.jpg', ['saas', 'digital menu', 'restaurant tech', 'qr code', 'restoran', 'indonesia'],
                    ['Platform SaaS menu digital berbasis QR yang memungkinkan restoran dan kafe memperbarui harga, foto, dan ketersediaan secara real-time.',
                        'SaaS landing page for a digital QR-code menu platform that lets restaurants and cafes update prices, photos, and availability in real time.',
                        'レストラン・カフェが価格・写真・提供状況をリアルタイムで更新できるQRデジタルメニューSaaS。'],
                    client: 'FTS'),
                'image_url' => '/images/projects/fts-menu.png',
                'live_url' => 'https://fts-menu.fts-tech.co.id',
                'tech_stack' => ['Laravel', 'Alpine.js', 'Tailwind CSS'],
            ],
            $this->portfolio('ens-hair-salon', 'ens. Hair Salon', 'web-development', 'beauty', 'ens.jpg', ['hair salon', 'beauty', 'company profile', 'hachioji', 'japan', 'jepang', '日本', '美容室'],
                ['Website bisnis untuk ens., salon rambut di Hachioji, Jepang, berisi konsep, menu dan harga, staf, galeri, dan kontak online.',
                    'Business website for ens., a hair salon in Hachioji, Japan, featuring their concept, menu and pricing, staff, gallery, and online contact.',
                    '八王子の美容室「ens.」のサイト。コンセプト、メニュー・料金、スタッフ、ギャラリー、オンラインお問い合わせを掲載しています。'],
                client: 'ens.'),
            [
                'slug' => 'restaurant-research-agent',
                'service' => 'ai-automation',
                'is_featured' => false,
                'name' => 'Restaurant Research Agent',
                'client_name' => 'Internal FTS',
                'industry' => 'internal',
                'status' => 'pilot',
                'image_url' => null,
                'tags' => ['ai agent', 'automation', 'lead research', 'sales', 'google sheets', 'jakarta'],
                'summary' => 'Rangkaian AI agent yang meriset restoran di Jakarta, menilai prospek, dan menyiapkan pesan penjualan.',
                'description' => 'Sistem multi-agent internal FTS: agent pertama meriset dan menilai restoran sebagai prospek, agent berikutnya mencari bukti dari web, mendiagnosis kebutuhan restoran, menulis pesan penjualan, dan mengklasifikasikan balasan untuk menentukan langkah selanjutnya. Hasil dikirim ke Google Sheets tim sales.',
                'highlights' => [
                    '5 agent dengan tugas berbeda dalam satu alur',
                    'Lead scoring berbasis data bisnis',
                    'Terhubung ke Google Sheets lewat Apps Script',
                ],
                'tech_stack' => ['Node.js', 'LLM agents', 'Google Apps Script'],
                'translations' => [
                    'en' => [
                        'name' => 'Restaurant Research Agent',
                        'summary' => 'A chain of AI agents that researches Jakarta restaurants, scores prospects and prepares sales messages.',
                        'description' => 'An internal FTS multi-agent system: the first agent researches and scores restaurants as prospects; the next ones gather web evidence, diagnose each restaurant\'s needs, write sales messages, and classify replies into next actions. Results flow into the sales team\'s Google Sheet.',
                        'highlights' => ['5 agents with distinct jobs in one pipeline', 'Lead scoring based on business data', 'Connected to Google Sheets via Apps Script'],
                    ],
                    'ja' => [
                        'name' => 'レストランリサーチエージェント',
                        'summary' => 'ジャカルタのレストランを調査し、見込み度を評価して営業メッセージを準備するAIエージェント群。',
                        'description' => 'FTS社内のマルチエージェントシステムです。最初のエージェントがレストランを調査・スコアリングし、続くエージェントがウェブ上の根拠収集、ニーズ診断、営業メッセージ作成、返信の分類と次のアクション判断を行います。結果は営業チームのGoogleスプレッドシートに送られます。',
                        'highlights' => ['役割の異なる5つのエージェントによるパイプライン', 'ビジネスデータに基づくリードスコアリング', 'Apps Script経由でGoogleスプレッドシートと連携'],
                    ],
                ],
            ],
        ];
    }

    /**
     * One entry from the official portfolio (fts-tech.co.id/projects/all).
     *
     * @param  list<string>  $tags
     * @param  array{0: string, 1: string, 2: string}  $summaries  [id, en, ja]
     * @return array<string, mixed>
     */
    private function portfolio(string $slug, string $name, string $service, string $industry, string $image, array $tags, array $summaries, ?string $client = null, bool $featured = false): array
    {
        [$id, $en, $ja] = $summaries;

        return [
            'slug' => $slug,
            'service' => $service,
            'is_featured' => $featured,
            'name' => $name,
            'client_name' => $client,
            'industry' => $industry,
            'status' => 'live',
            'image_url' => self::PORTFOLIO_IMAGES.$image,
            'tags' => array_values(array_unique($tags)),
            'summary' => $id,
            'description' => $id,
            'highlights' => [],
            'tech_stack' => [],
            'translations' => [
                'en' => ['summary' => $en, 'description' => $en],
                'ja' => ['summary' => $ja, 'description' => $ja],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function knowledge(): array
    {
        return [
            // --- about ---
            [
                'category' => 'about',
                'title' => 'Siapa FTS',
                'body' => 'Fujiyama Technology Solutions (FTS) adalah perusahaan IT berakar Jepang di Indonesia. FTS membantu bisnis di Indonesia dan Jepang meluncurkan inisiatif digital dengan delivery yang dapat diprediksi, dengan fokus pada pengembangan website di Indonesia, AI automation, pengembangan AI agent, dan offshore development. Tim kami memadukan standar kualitas Jepang dengan pemahaman pasar lokal. FTS juga membangun AI Website — website ini sendiri dijalankan oleh AI Website buatan FTS.',
                'tags' => ['fts', 'fujiyama', 'perusahaan', 'tentang', 'about', 'company', '会社', 'について', '富士山'],
                'translations' => [
                    'en' => ['title' => 'Who FTS is', 'body' => 'Fujiyama Technology Solutions (FTS) is a Japanese-rooted IT company in Indonesia. FTS helps Indonesian and Japanese businesses launch digital initiatives with predictable delivery, focusing on web development in Indonesia, AI automation, AI agent development, and offshore development. Our team blends Japanese quality standards with local market insight. FTS also builds AI Websites — this website itself runs on an AI Website built by FTS.'],
                    'ja' => ['title' => 'FTSについて', 'body' => 'Fujiyama Technology Solutions（FTS）は、インドネシアに拠点を置く日本発のIT企業です。インドネシアと日本の企業が確実にデジタル施策を進められるよう、インドネシアでのウェブ開発、AI自動化、AIエージェント開発、オフショア開発を中心に支援しています。日本の品質基準と現地市場への理解を組み合わせているのが強みです。また、AIウェブサイトも開発しており、このウェブサイト自体もFTSのAIウェブサイトで運営されています。'],
                ],
            ],
            // --- team (body: first line = role, rest = bio) ---
            [
                'category' => 'team',
                'title' => 'Yoshihiro Nakagawa',
                'body' => "Founder / President Director\nMemulai bisnis IT di Mongolia pada 2009 dan sejak itu berkecimpung di bisnis IT dan digital secara internasional. Sebagai pendiri FTS, ia menjunjung prinsip Jepang tentang kualitas dan tanggung jawab, dan ingin menjadikan FTS mitra teknologi jangka panjang yang menghubungkan Jepang dan Indonesia.",
                'tags' => ['pendiri', 'founder', 'presiden', 'president', 'ceo', 'pemilik', 'owner', 'nakagawa', 'yoshihiro', 'tim', 'team', 'pimpinan', 'leadership', '代表', '社長', '創業者', '中川'],
                'translations' => [
                    'en' => ['title' => 'Yoshihiro Nakagawa', 'body' => "Founder / President Director\nStarted his IT business in Mongolia in 2009 and has worked in IT and digital businesses internationally since. As the founder of FTS, he values the Japanese principles of quality and responsibility and aims to grow FTS into a long-term technology partner connecting Japan and Indonesia."],
                    'ja' => ['title' => '中川 義弘（Yoshihiro Nakagawa）', 'body' => "創業者／代表取締役\n2009年にモンゴルでIT事業を始めて以来、国際的にIT・デジタル事業に携わってきました。FTSの創業者として日本の「品質」と「責任」を大切にし、日本とインドネシアをつなぐ長期的なテクノロジーパートナーとしてFTSを成長させることを目指しています。"],
                ],
            ],
            [
                'category' => 'team',
                'title' => 'Takakazu Kaburaki',
                'body' => "Chief Operating Officer\nMengawasi operasional harian dengan fokus pada keunggulan dan efisiensi, serta memastikan setiap proyek berhasil dan pelanggan puas. Berbasis di Jepang, ia bekerja erat dengan tim FTS di Indonesia sebagai jembatan komunikasi antara kedua negara.",
                'tags' => ['coo', 'operasional', 'operations', 'kaburaki', 'takakazu', 'tim', 'team', 'pimpinan', 'leadership', '鏑木', 'COO'],
                'translations' => [
                    'en' => ['title' => 'Takakazu Kaburaki', 'body' => "Chief Operating Officer\nOversees day-to-day operations with a strong focus on excellence and efficiency, ensuring successful project delivery and customer satisfaction. Based in Japan, he works closely with the FTS team in Indonesia as a bridge between the two countries."],
                    'ja' => ['title' => '鏑木 孝和（Takakazu Kaburaki）', 'body' => "最高執行責任者（COO）\n卓越性と効率を重視して日々の運営を統括し、各プロジェクトの確実な遂行とお客様満足を担っています。日本を拠点に、インドネシアのFTSチームと密に連携し、両国の橋渡し役を務めています。"],
                ],
            ],
            [
                'category' => 'team',
                'title' => 'Naoki Yoshida',
                'body' => "Director\nMengawasi strategi teknologi dan inovasi FTS. Peka terhadap tren yang sedang berkembang dan bersemangat mengembangkan solusi mutakhir yang memecahkan masalah nyata bagi klien.",
                'tags' => ['direktur', 'director', 'yoshida', 'naoki', 'teknologi', 'inovasi', 'tim', 'team', '吉田', '取締役'],
                'translations' => [
                    'en' => ['title' => 'Naoki Yoshida', 'body' => "Director\nOversees FTS's technology and innovation strategy. He has a keen eye for emerging trends and is passionate about developing cutting-edge solutions that solve real-world problems for clients."],
                    'ja' => ['title' => '吉田 直樹（Naoki Yoshida）', 'body' => "取締役\nFTSの技術・イノベーション戦略を統括。新しいトレンドに敏感で、お客様の実際の課題を解決する先進的なソリューションの開発に情熱を注いでいます。"],
                ],
            ],
            [
                'category' => 'team',
                'title' => 'Takaki Morita',
                'body' => "Director\nMendukung FTS sejak awal berdiri dan tetap aktif dalam pertumbuhan serta pengembangan teknologinya. Bertanggung jawab utama atas bidang AI — mengeksplorasi pemanfaatan praktis teknologi AI dan mengintegrasikannya ke layanan FTS.",
                'tags' => ['direktur', 'director', 'morita', 'takaki', 'ai', 'tim', 'team', '森田', '取締役'],
                'translations' => [
                    'en' => ['title' => 'Takaki Morita', 'body' => "Director\nHas supported FTS since its founding and remains actively involved in its growth and technology development. Primarily responsible for the AI field — exploring the practical use of AI technologies and integrating them into FTS services."],
                    'ja' => ['title' => '森田 貴樹（Takaki Morita）', 'body' => "取締役\n創業時からFTSを支え、成長と技術開発に携わり続けています。AI分野を主に担当し、AI技術の実用化とFTSのサービスへの統合を推進しています。"],
                ],
            ],

            // --- values ---
            [
                'category' => 'values',
                'title' => 'Inovasi',
                'body' => 'Kami memakai teknologi terkini dan solusi kreatif untuk memecahkan tantangan yang kompleks.',
                'tags' => ['nilai', 'values', 'inovasi', 'innovation', 'budaya', 'culture', '価値観', '理念', 'イノベーション'],
                'translations' => [
                    'en' => ['title' => 'Innovation', 'body' => 'We embrace cutting-edge technologies and creative solutions to solve complex challenges.'],
                    'ja' => ['title' => 'イノベーション', 'body' => '最新技術と創造的な解決策で、複雑な課題に取り組みます。'],
                ],
            ],
            [
                'category' => 'values',
                'title' => 'Kualitas',
                'body' => 'Setiap proyek dikerjakan dengan perhatian penuh pada detail dan standar tertinggi.',
                'tags' => ['nilai', 'values', 'kualitas', 'quality', 'jepang', 'japanese quality', '品質'],
                'translations' => [
                    'en' => ['title' => 'Quality', 'body' => 'Every project is delivered with meticulous attention to detail and the highest standards.'],
                    'ja' => ['title' => '品質', 'body' => '細部まで配慮し、最高水準でプロジェクトをお届けします。'],
                ],
            ],
            [
                'category' => 'values',
                'title' => 'Transparansi',
                'body' => 'Komunikasi terbuka dan kemitraan yang jujur adalah fondasi setiap hubungan kami.',
                'tags' => ['nilai', 'values', 'transparansi', 'transparency', 'komunikasi', '透明性'],
                'translations' => [
                    'en' => ['title' => 'Transparency', 'body' => 'Open communication and honest partnerships are the foundation of our relationships.'],
                    'ja' => ['title' => '透明性', 'body' => 'オープンなコミュニケーションと誠実なパートナーシップが、すべての関係の土台です。'],
                ],
            ],
            [
                'category' => 'values',
                'title' => 'Pertumbuhan',
                'body' => 'Kami terus belajar dan membantu klien mencapai pertumbuhan yang berkelanjutan.',
                'tags' => ['nilai', 'values', 'pertumbuhan', 'growth', 'belajar', '成長'],
                'translations' => [
                    'en' => ['title' => 'Growth', 'body' => 'We are committed to continuous learning and helping our clients achieve sustainable growth.'],
                    'ja' => ['title' => '成長', 'body' => '学び続け、お客様の持続的な成長を支援します。'],
                ],
            ],
            [
                'category' => 'about',
                'title' => 'Mengapa memilih FTS',
                'body' => 'Perusahaan IT berakar Jepang di Indonesia dengan koordinasi dua bahasa. Produksi website dan otomasi bisnis berbasis AI. Pembuatan website cepat dengan paket bulanan terjangkau. Integrasi WhatsApp dan struktur website SEO-friendly. Dukungan offshore development untuk perusahaan Jepang. Pengembangan AI agent untuk sales dan customer service.',
                'tags' => ['kenapa', 'mengapa', 'keunggulan', 'why', 'advantage', 'jepang', 'japan', 'kualitas', 'quality', '強み', 'なぜ', '日本品質'],
                'translations' => [
                    'en' => ['title' => 'Why choose FTS', 'body' => 'A Japanese-rooted IT company in Indonesia with bilingual coordination. AI-powered website production and business automation. Fast website development with affordable monthly plans. WhatsApp integration and SEO-friendly website structure. Offshore development support for Japanese companies. AI agent development for sales support and customer service workflows.'],
                    'ja' => ['title' => 'FTSが選ばれる理由', 'body' => 'バイリンガルで進行できる、インドネシアの日本発IT企業。AIを活用したウェブ制作と業務自動化。手頃な月額プランでのスピーディーなサイト制作。WhatsApp連携とSEOに強いサイト構造。日本企業向けのオフショア開発支援。営業・カスタマーサービス向けのAIエージェント開発。'],
                ],
            ],
            [
                'category' => 'about',
                'title' => 'Industri yang kami dukung',
                'body' => 'Restoran, kafe, salon, dan bisnis hospitality. Perusahaan, brand retail, dan layanan profesional. Startup dan UMKM yang sedang mengembangkan digital marketing. Perusahaan Jepang yang membutuhkan offshore development di Indonesia. Portofolio kami mencakup klien di Indonesia, Jepang, dan Thailand.',
                'tags' => ['industri', 'klien', 'pelanggan', 'industry', 'clients', 'restoran', 'hotel', 'salon', 'umkm', 'sme', '業種', '対象', 'お客様'],
                'translations' => [
                    'en' => ['title' => 'Industries we support', 'body' => 'Restaurants, cafés, salons and hospitality businesses. Companies, retail brands and professional services. Startups and SMEs scaling their digital marketing. Japanese companies requiring offshore development in Indonesia. Our portfolio includes clients in Indonesia, Japan and Thailand.'],
                    'ja' => ['title' => '対応業種', 'body' => 'レストラン、カフェ、サロン、ホスピタリティ業。企業、小売ブランド、専門サービス業。デジタルマーケティングを強化したいスタートアップ・中小企業。インドネシアでのオフショア開発を必要とする日本企業。実績にはインドネシア、日本、タイのお客様が含まれます。'],
                ],
            ],
            [
                'category' => 'about',
                'title' => 'Pasar Indonesia dan Jepang',
                'body' => 'FTS menggabungkan kualitas Jepang dan keunggulan Indonesia. Kami melayani bisnis di Indonesia dan Jepang — banyak proyek portofolio kami dibuat untuk klien di Jepang, dan kami menyediakan tim offshore di Indonesia untuk perusahaan Jepang. Website, AI Staff, dan produk kami mendukung Bahasa Indonesia, Inggris, dan Jepang.',
                'tags' => ['jepang', 'japan', 'indonesia', 'bahasa', 'language', '日本', 'インドネシア', '言語'],
                'translations' => [
                    'en' => ['title' => 'Indonesia and Japan markets', 'body' => 'FTS combines Japanese quality with Indonesian excellence. We serve businesses in Indonesia and Japan — many of our portfolio projects were built for clients in Japan, and we provide offshore teams in Indonesia for Japanese companies. Our websites, AI Staff and products support Indonesian, English and Japanese.'],
                    'ja' => ['title' => 'インドネシアと日本の市場', 'body' => 'FTSは日本の品質とインドネシアの力を掛け合わせています。インドネシアと日本の企業を支援しており、実績の多くは日本のお客様向けです。また、日本企業向けにインドネシアでのオフショア開発チームを提供しています。ウェブサイト、AIスタッフ、各製品はインドネシア語・英語・日本語に対応しています。'],
                ],
            ],

            // --- process ---
            [
                'category' => 'process',
                'title' => 'Discovery',
                'body' => 'Kami memetakan tujuan, KPI, dan peluang otomasi bisnis Anda.',
                'tags' => ['proses', 'langkah', 'mulai', 'alur', 'process', 'steps', 'workflow', '流れ', '進め方'],
                'translations' => [
                    'en' => ['title' => 'Discovery', 'body' => 'We map your goals, KPIs and automation opportunities.'],
                    'ja' => ['title' => 'ディスカバリー', 'body' => '目標、KPI、自動化できるポイントを整理します。'],
                ],
            ],
            [
                'category' => 'process',
                'title' => 'Delivery',
                'body' => 'Pengerjaan dengan milestone yang jelas, QA, dan update mingguan.',
                'tags' => ['delivery', 'milestone', 'qa', 'update', 'mingguan', 'weekly', '進捗', '品質'],
                'translations' => [
                    'en' => ['title' => 'Delivery', 'body' => 'Delivery with clear milestones, QA and weekly updates.'],
                    'ja' => ['title' => 'デリバリー', 'body' => '明確なマイルストーン、QA、週次の進捗報告で開発を進めます。'],
                ],
            ],
            [
                'category' => 'process',
                'title' => 'Optimasi',
                'body' => 'Kami terus meningkatkan performa dan perolehan lead setelah peluncuran.',
                'tags' => ['optimasi', 'optimization', 'setelah launch', 'lead', '改善', '最適化'],
                'translations' => [
                    'en' => ['title' => 'Optimization', 'body' => 'We keep improving performance and lead generation after launch.'],
                    'ja' => ['title' => '最適化', 'body' => '公開後もパフォーマンスと問い合わせ獲得の改善を続けます。'],
                ],
            ],

            // --- pricing ---
            [
                'category' => 'pricing',
                'title' => 'Paket website bulanan',
                'body' => 'Desain & pembuatan website GRATIS; Anda membayar biaya server bulanan. Hingga 5 halaman: Rp700.000/bulan (≈ $42,42). Hingga 10 halaman (paling populer): Rp1.200.000/bulan (≈ $72,73). Hingga 15 halaman: Rp1.600.000/bulan (≈ $96,97). Semua paket kontrak 36 bulan, 6 bulan terakhir gratis. Lebih dari 15 halaman: penawaran harga, disesuaikan dengan scope, fitur, dan timeline. Tanpa biaya tersembunyi.',
                'tags' => ['harga', 'biaya', 'paket', 'website', 'bulanan', 'price', 'pricing', 'plan', 'monthly', 'cost', 'server', 'kontrak', '料金', '価格', '費用', '月額', 'プラン'],
                'translations' => [
                    'en' => ['title' => 'Monthly website plans', 'body' => 'Website design & development is FREE; you pay a monthly server fee. Up to 5 pages: Rp700,000/month (≈ $42.42). Up to 10 pages (most popular): Rp1,200,000/month (≈ $72.73). Up to 15 pages: Rp1,600,000/month (≈ $96.97). All plans are 36-month contracts with the last 6 months free. More than 15 pages: quotation, tailored to scope, features and timeline. No hidden fees.'],
                    'ja' => ['title' => 'ウェブサイト月額プラン', 'body' => 'ウェブサイトのデザイン・制作費は無料で、月額のサーバー費用のみいただきます。5ページまで：月額Rp700,000（約$42.42）。10ページまで（人気No.1）：月額Rp1,200,000（約$72.73）。15ページまで：月額Rp1,600,000（約$96.97）。いずれも36ヶ月契約で、最後の6ヶ月は無料です。15ページを超える場合は、範囲・機能・スケジュールに合わせてお見積りします。追加費用はありません。'],
                ],
            ],
            [
                'category' => 'pricing',
                'title' => 'Kebijakan harga',
                'body' => 'Harga yang dipublikasikan: paket website bulanan dan paket FTS Menu (mulai dari Free). AI Website, AI automation, offshore development, dan proyek khusus diberi penawaran sesuai ruang lingkup setelah konsultasi gratis; penawaran proyek biasanya dikirim dalam 24 jam. Diskon atau harga khusus hanya dapat diberikan oleh tim FTS.',
                'tags' => ['harga', 'biaya', 'price', 'cost', 'diskon', 'discount', 'penawaran', 'quotation', '料金', '価格', '費用', '見積'],
                'translations' => [
                    'en' => ['title' => 'Pricing policy', 'body' => 'Published prices: the monthly website plans and FTS Menu plans (starting with Free). AI Websites, AI automation, offshore development and custom projects are quoted to scope after a free consultation; project quotes are usually sent within 24 hours. Discounts or special pricing can only be offered by the FTS team.'],
                    'ja' => ['title' => '料金について', 'body' => '公開している料金は、ウェブサイト月額プランとFTS Menuのプラン（Freeあり）です。AIウェブサイト、AI自動化、オフショア開発、個別案件は、無料相談の後に範囲に応じてお見積りします（通常24時間以内にご提示）。割引や特別価格はFTSチームのみがご案内できます。'],
                ],
            ],

            // --- faq ---
            [
                'category' => 'faq',
                'title' => 'Apa bedanya AI Website dengan chatbot biasa?',
                'body' => 'Chatbot biasa hanya menjawab pertanyaan dari skrip. AI Staff di AI Website bekerja di dalam website: memakai knowledge base dan data bisnis nyata, menampilkan produk, harga, dan foto langsung di percakapan, mencatat reservasi atau permintaan konsultasi, dan menyerahkan percakapan ke staf manusia bila perlu. AI Website juga dirancang untuk terhubung dengan website lain dan platform yang lebih besar.',
                'tags' => ['chatbot', 'beda', 'perbedaan', 'difference', 'ai website', 'チャットボット', '違い'],
                'translations' => [
                    'en' => ['title' => 'How is an AI Website different from a normal chatbot?', 'body' => 'A normal chatbot only answers from a script. The AI Staff in an AI Website works inside the site: it uses a real knowledge base and business data, shows products, prices and photos right in the conversation, records reservations or consultation requests, and hands the conversation to a human when needed. AI Websites are also designed to connect with other websites and a larger platform.'],
                    'ja' => ['title' => 'AIウェブサイトと普通のチャットボットの違いは？', 'body' => '普通のチャットボットは決められたスクリプトで答えるだけです。AIウェブサイトのAIスタッフはウェブサイトの中で働き、実際のナレッジベースとビジネスデータを使い、商品・料金・写真を会話の中で表示し、予約や相談のリクエストを記録し、必要に応じてスタッフへ引き継ぎます。また、他のウェブサイトや大規模プラットフォームとの連携も想定しています。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Berapa lama pembuatan website?',
                'body' => 'Sebagian besar proyek memakan waktu 3 sampai 6 minggu, tergantung scope, konten, dan integrasi seperti WhatsApp atau sistem booking.',
                'tags' => ['berapa lama', 'waktu', 'durasi', 'timeline', 'how long', 'minggu', 'weeks', '期間', 'どのくらい', '納期'],
                'translations' => [
                    'en' => ['title' => 'How long does website development take?', 'body' => 'Most projects take 3 to 6 weeks depending on scope, content, and integrations like WhatsApp or booking systems.'],
                    'ja' => ['title' => 'ウェブサイト制作の期間は？', 'body' => '多くの案件は3〜6週間です。範囲、コンテンツ量、WhatsAppや予約システムなどの連携内容によって変わります。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Apakah FTS menyediakan dukungan digital marketing?',
                'body' => 'Ya. Kami mendukung perencanaan konten SEO, setup analytics, dan landing page untuk digital marketing berkelanjutan.',
                'tags' => ['digital marketing', 'seo', 'marketing', 'analytics', 'iklan', 'マーケティング'],
                'translations' => [
                    'en' => ['title' => 'Do you provide digital marketing support?', 'body' => 'Yes. We support SEO content planning, analytics setup, and landing pages for ongoing digital marketing.'],
                    'ja' => ['title' => 'デジタルマーケティングの支援はありますか？', 'body' => 'はい。SEOコンテンツの企画、分析ツールの設定、ランディングページ制作など、継続的なデジタルマーケティングを支援します。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Bisakah mulai dari otomasi kecil?',
                'body' => 'Bisa. Kami sering memulai dengan satu alur kerja, memvalidasi ROI, lalu memperluasnya ke program otomasi bisnis yang lebih besar. Kami juga menyediakan dukungan AI agent berkelanjutan: monitoring, prompt tuning, dan optimasi.',
                'tags' => ['otomasi kecil', 'mulai', 'automation', 'small', 'roi', 'ai agent', 'support', '小さく', '自動化'],
                'translations' => [
                    'en' => ['title' => 'Can we start with a small automation?', 'body' => 'Yes. We often begin with one workflow, validate ROI, and expand to broader business automation programs. We also provide ongoing AI agent support: monitoring, prompt tuning and continuous optimization.'],
                    'ja' => ['title' => '小さな自動化から始められますか？', 'body' => 'はい。まずひとつのワークフローでROIを検証し、より広い業務自動化へ段階的に広げることが多いです。AIエージェントの監視、プロンプト調整、継続的な最適化などの運用サポートも提供しています。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Bagaimana offshore development berjalan?',
                'body' => 'Tim offshore kami mendukung proyek jangka panjang dan dapat bertambah seiring waktu dengan review performa bulanan. Komunikasi melalui sync mingguan, dashboard proyek bersama, dan laporan dua bahasa agar semua pihak tetap selaras.',
                'tags' => ['offshore', 'jangka panjang', 'komunikasi', 'long-term', 'communication', 'オフショア', '長期', 'コミュニケーション'],
                'translations' => [
                    'en' => ['title' => 'How does offshore development work?', 'body' => 'Our offshore teams support long-term projects and scale over time with clear monthly performance reviews. Communication runs through weekly syncs, shared project dashboards and bilingual reporting to keep stakeholders aligned.'],
                    'ja' => ['title' => 'オフショア開発はどのように進みますか？', 'body' => '長期案件に対応しており、月次のパフォーマンスレビューを行いながらチームを拡張できます。週次ミーティング、共有ダッシュボード、バイリンガルのレポートで関係者と足並みをそろえます。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Apakah AI bisa memberi jawaban yang salah?',
                'body' => 'AI Staff hanya boleh menjawab fakta perusahaan dari knowledge base yang disetujui, dan harga dari database. Jika informasinya tidak ada, AI tidak menebak — ia menawarkan untuk menghubungkan pengunjung dengan tim. Semua percakapan bisa dipantau dari dashboard admin.',
                'tags' => ['salah', 'akurat', 'halusinasi', 'mistake', 'accurate', 'wrong', '間違', '正確'],
                'translations' => [
                    'en' => ['title' => 'Can the AI give wrong answers?', 'body' => 'The AI Staff may only answer company facts from the approved knowledge base, and prices from the database. When information is missing it does not guess — it offers to connect the visitor with the team. Every conversation can be reviewed in the admin dashboard.'],
                    'ja' => ['title' => 'AIが間違った回答をすることはありますか？', 'body' => 'AIスタッフは、会社の情報は承認済みのナレッジベースから、料金はデータベースからのみ回答します。情報がない場合は推測せず、スタッフへの引き継ぎをご案内します。すべての会話は管理画面で確認できます。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Bahasa apa saja yang didukung?',
                'body' => 'Bahasa Indonesia, Inggris, dan Jepang. AI Staff menjawab dalam bahasa yang dipilih pengunjung dan mengikuti jika pengunjung berganti bahasa. Tim kami juga berkoordinasi dalam dua bahasa dengan klien Jepang.',
                'tags' => ['bahasa', 'language', 'inggris', 'jepang', 'english', 'japanese', '言語', '日本語'],
                'translations' => [
                    'en' => ['title' => 'Which languages are supported?', 'body' => 'Indonesian, English and Japanese. The AI Staff replies in the visitor\'s chosen language and follows along if they switch. Our team also coordinates bilingually with Japanese clients.'],
                    'ja' => ['title' => '対応言語は？', 'body' => 'インドネシア語、英語、日本語です。AIスタッフは訪問者が選んだ言語で回答し、途中で言語が変わっても対応します。日本のお客様とはバイリンガルで進行します。'],
                ],
            ],
            [
                'category' => 'faq',
                'title' => 'Apakah data kami aman?',
                'body' => 'Untuk offshore development kami menerapkan kontrol akses aman dan alur kerja sesuai NDA. AI Website versi saat ini memakai model AI yang di-host sendiri (self-hosted), bukan layanan chatbot umum, dan AI Staff hanya membaca data yang Anda izinkan di dashboard. Detail infrastruktur dan keamanan untuk proyek Anda dibahas saat konsultasi.',
                'tags' => ['data', 'aman', 'keamanan', 'privasi', 'nda', 'security', 'privacy', 'server', 'セキュリティ', '安全', '秘密保持'],
                'translations' => [
                    'en' => ['title' => 'Is our data safe?', 'body' => 'For offshore development we use secure access controls and NDA-compliant workflows. The current AI Website uses a self-hosted AI model rather than a public chatbot service, and the AI Staff only reads the data you allow in the dashboard. Infrastructure and security details for your project are discussed during the consultation.'],
                    'ja' => ['title' => 'データは安全ですか？', 'body' => 'オフショア開発では、安全なアクセス管理とNDAに準拠した運用を行っています。現在のAIウェブサイトは一般的なチャットボットサービスではなく自社運用のAIモデルを使用し、AIスタッフは管理画面で許可されたデータのみを参照します。御社プロジェクトのインフラやセキュリティの詳細はご相談時にご説明します。'],
                ],
            ],

            // --- contact & support ---
            [
                'category' => 'contact',
                'title' => 'Kantor dan kontak FTS',
                'body' => 'Kantor pusat Jakarta: Neo Soho Mall, Jl. Let. Jend. S. Parman Kav. 28 Unit 2011, Tanjung Duren Selatan, Grogol Petamburan, Jakarta Barat, DKI Jakarta 11470, Indonesia. Jam kerja: Senin–Jumat, 09.00–18.00 WIB. Telepon: +62 895 2933 6179 (juga +62 811 8999 7757). Email: info@fts-tech.co.id. Website: fts-tech.co.id. Anda juga bisa menyampaikan nama, kontak, dan kebutuhan kepada AI Staff di sini, dan tim FTS akan menindaklanjuti.',
                'tags' => ['kontak', 'hubungi', 'alamat', 'kantor', 'lokasi', 'jam kerja', 'telepon', 'contact', 'address', 'office', 'location', 'hours', 'phone', 'email', 'neo soho', 'jakarta', '連絡', '問い合わせ', '住所', 'オフィス', '営業時間'],
                'translations' => [
                    'en' => ['title' => 'FTS office and contact', 'body' => 'Jakarta headquarters: Neo Soho Mall, Jalan Let. Jend. S. Parman Kav. 28 Unit 2011, Tanjung Duren Selatan, Grogol Petamburan, West Jakarta, DKI Jakarta 11470, Indonesia. Business hours: Monday–Friday, 9 AM–6 PM (WIB). Phone: +62 895 2933 6179 (also +62 811 8999 7757). Email: info@fts-tech.co.id. Website: fts-tech.co.id. You can also share your name, contact and needs with the AI Staff here, and the FTS team will follow up.'],
                    'ja' => ['title' => 'FTSのオフィス・連絡先', 'body' => 'ジャカルタ本社：Neo Soho Mall, Jalan Let. Jend. S. Parman Kav. 28 Unit 2011, Tanjung Duren Selatan, Grogol Petamburan, West Jakarta, DKI Jakarta 11470, Indonesia。営業時間：月〜金 9:00〜18:00（ジャカルタ時間）。電話：+62 895 2933 6179（+62 811 8999 7757）。メール：info@fts-tech.co.id。ウェブサイト：fts-tech.co.id。こちらのAIスタッフにお名前・ご連絡先・ご要望をお伝えいただければ、FTSチームよりご連絡いたします。'],
                ],
            ],
            [
                'category' => 'support',
                'title' => 'Waktu respons',
                'body' => 'Pertanyaan via email: dibalas dalam 4 jam. Telepon: langsung. Penawaran proyek: dalam 24 jam. Untuk pertanyaan mendesak, hubungi kami langsung atau jadwalkan konsultasi.',
                'tags' => ['respons', 'balas', 'cepat', 'mendesak', 'response time', 'reply', 'urgent', '返信', '対応時間', '急ぎ'],
                'translations' => [
                    'en' => ['title' => 'Response times', 'body' => 'Email inquiries: within 4 hours. Phone calls: immediate. Project quotes: within 24 hours. For urgent inquiries, call us directly or schedule a consultation.'],
                    'ja' => ['title' => '対応時間', 'body' => 'メールでのお問い合わせ：4時間以内に返信。お電話：即時対応。プロジェクトのお見積り：24時間以内。お急ぎの場合はお電話、またはご相談の予約をお願いします。'],
                ],
            ],
            [
                'category' => 'support',
                'title' => 'Dukungan untuk pelanggan',
                'body' => 'Pelanggan yang sudah menggunakan layanan FTS dapat meminta bantuan melalui AI Staff; permintaan dukungan teknis akan diteruskan langsung ke tim FTS. Paket website bulanan sudah termasuk dukungan berkelanjutan.',
                'tags' => ['bantuan', 'dukungan', 'support', 'masalah', 'problem', 'maintenance', 'サポート', '問題', '保守'],
                'translations' => [
                    'en' => ['title' => 'Customer support', 'body' => 'Existing FTS customers can ask for help through the AI Staff; technical support requests are passed straight to the FTS team. Monthly website plans include ongoing support.'],
                    'ja' => ['title' => 'お客様サポート', 'body' => 'FTSのサービスをご利用中のお客様は、AIスタッフからサポートをご依頼いただけます。技術的なご依頼はFTSチームへ直接引き継がれます。ウェブサイト月額プランには継続的なサポートが含まれます。'],
                ],
            ],
        ];
    }
}
