<?php
/**
 * IIT Indore (IITI) Sustainable Technologies Laboratory (STLab) Portal
 * Version: 1.0.0
 * 
 * Description: 
 * This PHP script is a standalone, single-file distribution of the STLab academic portal.
 * It is fully responsive, highly optimized, and tailored for standard institutional Apache/PHP web hosting 
 * servers (e.g. iiti.ac.in/~username/).
 * 
 * Features:
 * - Dynamic tab system (supports instant client-side transitions and direct linkable queries like index.php?tab=research)
 * - Dynamic server-side PHP contact form validation, logging, and success screens
 * - Live synchronized laboratory UTC Server Clock
 * - Full-fidelity presentation of Research Topics, Sanskrit Shloka, Team Profiles (People), publications, and Simrol campus maps.
 */

// --- PHP FORM BACKEND HANDLER ---
$feedbackMessage = null;
$feedbackClass = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    if ($_POST["action"] === "contact_submit") {
        $name = filter_var($_POST["name"] ?? "", FILTER_SANITIZE_SPECIAL_CHARS);
        $designation = filter_var($_POST["designation"] ?? "", FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_var($_POST["email"] ?? "", FILTER_VALIDATE_EMAIL);
        $message = filter_var($_POST["message"] ?? "", FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($name) || empty($designation) || !$email || empty($message)) {
            $feedbackMessage = "Submission failed: Please provide a valid email, name, designation, and suggestion details.";
            $feedbackClass = "bg-red-50 text-red-705 border-red-200";
        } else {
            // Log submission locally to submissions directory (if server has write permissions)
            $logDir = __DIR__ . '/submissions';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            $logFile = $logDir . '/contact_submissions.json';
            $submissionData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'name' => $name,
                'designationOrInstitute' => $designation,
                'email' => $email,
                'query' => $message,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ];
            
            $existingLogs = [];
            if (file_exists($logFile)) {
                $content = @file_get_contents($logFile);
                $existingLogs = json_decode($content, true) ?: [];
            }
            $existingLogs[] = $submissionData;
            @file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT));

            // Transmit direct academic email to Principal Investigator at ganti.murthy@iiti.ac.in
            $to = "ganti.murthy@iiti.ac.in";
            $subject = "STLab Portal Academic Query - " . $name;
            $emailContent = "Dear Prof. Ganti S. Murthy,\n\n" .
                            "A new academic query/suggestion has been submitted via the STLab Portal:\n\n" .
                            "--------------------------------------------------\n" .
                            "Name: " . $name . "\n" .
                            "Designation / Institute: " . $designation . "\n" .
                            "Contact Email: " . $email . "\n" .
                            "--------------------------------------------------\n\n" .
                            "Query Details:\n" . $message . "\n\n" .
                            "--------------------------------------------------\n" .
                            "Submitting IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n" .
                            "Submission Timestamp: " . date('Y-m-d H:i:s') . "\n\n" .
                            "This notification was automatically dispatched from the IITI Server Host.";
            
            $headers = "From: stlab-webmaster@iiti.ac.in\r\n" .
                       "Reply-To: " . $email . "\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            // Suppress error in case local development env doesn't have system mail utility (fallback logging is active)
            $mailDispatched = @mail($to, $subject, $emailContent, $headers);

            if ($mailDispatched) {
                $feedbackMessage = "✓ Success! Your suggestion has been successfully compiled and emailed directly to Prof. Ganti S. Murthy (ganti.murthy@iiti.ac.in).";
            } else {
                $feedbackMessage = "✓ Suggestion Received! Your query was saved on the server and queued for routing to Prof. Ganti S. Murthy (ganti.murthy@iiti.ac.in).";
            }
            $feedbackClass = "bg-emerald-50 text-emerald-800 border-emerald-200 shadow-sm";
        }
    }
}

// --- PHP TAB FALLBACK DETERMINATOR ---
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'home';
if (!in_array($activeTab, ['home', 'news', 'research', 'people', 'publications', 'learning', 'contact', 'gallery', 'weather'])) {
    $activeTab = 'home';
}

$titles = [
    "home" => "Sustainable Technologies Laboratory (STLab) | IIT Indore",
    "research" => "Research Areas | STLab | IIT Indore",
    "people" => "Research Team | STLab | IIT Indore",
    "publications" => "Publications | STLab | IIT Indore",
    "news" => "News | STLab | IIT Indore",
    "learning" => "Learning Resources | STLab | IIT Indore",
    "gallery" => "Lab Gallery | STLab | IIT Indore",
    "weather" => "IITI Weather Statistics | STLab | IIT Indore",
    "contact" => "Contact | STLab | IIT Indore"
];

$descriptions = [
    "home" => "Official website of Sustainable Technologies Laboratory led by Prof. G. S. Murthy at IIT Indore. Research in Sustainable Technologies, Indian Knowledge Systems (IKS), Traditional Ecological Knowledge (TEK), wastewater treatment and bioenergy.",
    "research" => "Research in sustainable technologies, systems engineering, IKS, TEK, circular economy, wastewater treatment and bioresource engineering.",
    "people" => "Meet the researchers, doctoral students, and scholars of STLab, IIT Indore.",
    "publications" => "Research publications from Sustainable Technologies Laboratory.",
    "news" => "Latest news and announcements from Sustainable Technologies Laboratory (STLab) at IIT Indore.",
    "learning" => "Learning resources, tutorials, and academic materials provided by STLab.",
    "gallery" => "Browse photos of the laboratory, students, events, and campus life at STLab, IIT Indore.",
    "weather" => "Real-time weather statistics and monitoring from IIT Indore campus.",
    "contact" => "Get in touch with the Sustainable Technologies Laboratory at IIT Indore."
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titles[$activeTab] ?? $titles['home']; ?></title>
    <meta name="description" content="<?= $descriptions[$activeTab] ?? $descriptions['home']; ?>">
    <link rel="canonical" href="https://stlab.iiti.ac.in/?tab=<?=$activeTab?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?= $titles[$activeTab] ?? $titles['home']; ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://stlab.iiti.ac.in/?tab=<?=$activeTab?>">
    <meta property="og:image" content="https://stlab.iiti.ac.in/assets/STL_New_PNG.png">
    <meta property="og:description" content="<?= $descriptions[$activeTab] ?? $descriptions['home']; ?>">
    
    <!-- Structured Data (Schema.org) -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "EducationalOrganization",
      "name": "Sustainable Technologies Laboratory (STLab)",
      "alternateName": "STLab, IIT Indore",
      "url": "https://stlab.iiti.ac.in",
      "logo": "https://stlab.iiti.ac.in/assets/STL_New_PNG.png",
      "parentOrganization": {
        "@type": "EducationalOrganization",
        "name": "Indian Institute of Technology Indore",
        "url": "https://www.iiti.ac.in"
      },
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Khandwa Road, Simrol",
        "addressLocality": "Indore",
        "addressRegion": "Madhya Pradesh",
        "postalCode": "453552",
        "addressCountry": "IN"
      },
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "ganti.murthy@iiti.ac.in",
        "contactType": "academic inquiry"
      },
      "founder": {
        "@type": "Person",
        "name": "Prof. Ganti S. Murthy"
      },
      "description": "Research laboratory at IIT Indore focusing on Sustainable Technologies, Indian Knowledge Systems (IKS), Traditional Ecological Knowledge (TEK), wastewater treatment, and bioenergy."
    }
    </script>
    <!-- Tailwind CSS Dynamic Integration -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'st-orange': '#f17a1f',
                        'st-green': '#2b9322',
                        'st-blue': '#0c2340',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        };
    </script>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/stlab-logo.png?v=3" />
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&family=Tiro+Devanagari+Sanskrit:ital@0;1&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: transparent;
        }
        .font-sanskrit {
            font-family: 'Tiro Devanagari Sanskrit', serif !important;
        }
        .card-3d-bevel {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(226, 232, 240, 0.8);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-3d-bevel:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            border-color: rgba(241, 122, 31, 0.25);
        }
        .animate-tabFadeIn {
            animation: tabFade 0.25s ease-out forwards;
        }
        @keyframes tabFade {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes marquee {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        .animate-marquee {
            display: flex;
            gap: 3rem;
            width: max-content;
            animation: marquee 35s linear infinite;
        }
        .animate-marquee:hover {
            animation-play-state: paused;
        }
        @keyframes phpTwinkle {
            0%, 100% {
                opacity: 0.1;
                transform: scale(0.6) translateY(0);
            }
            50% {
                opacity: 0.85;
                transform: scale(1.15) translateY(-15px);
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between text-slate-800 antialiased selection:bg-st-orange/30 selection:text-white relative">

    <!-- Fixed elegant light/white-themed background with soft logo shades -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden select-none" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: -10;">
        <!-- Base light fade-out radial gradient from complete white to premium soft gray-blue -->
        <div class="absolute inset-0" style="position: absolute; inset: 0; background: radial-gradient(circle at center, #ffffff 0%, #f4f7fa 100%);"></div>
        
        <!-- Ambient glowing faded color blobs (extremely low opacity for soft aesthetic) -->
        <div class="absolute rounded-full" style="position: absolute; top: 8%; left: 15%; width: 60vw; height: 60vh; background: rgba(12, 35, 64, 0.025); filter: blur(140px); animation: pulse 16s infinite ease-in-out;"></div>
        <div class="absolute rounded-full" style="position: absolute; bottom: 15%; right: 10%; width: 55vw; height: 55vh; background: rgba(241, 122, 31, 0.02); filter: blur(150px); animation: pulse 20s infinite ease-in-out;"></div>
        <div class="absolute rounded-full" style="position: absolute; top: 35%; right: 25%; width: 50vw; height: 50vh; background: rgba(43, 147, 34, 0.025); filter: blur(130px); animation: pulse 18s infinite ease-in-out;"></div>
        
        <!-- Pastel fine glitter particles overlay for light background -->
        <?php for ($i = 0; $i < 34; $i++): 
            $pRandom = sin($i * 92837.283) * 0.5 + 0.5;
            
            // Soft academic logo shades suited for a beautiful white theme
            $color = 'rgba(12, 35, 64, 0.22)'; // Soft Navy-slate-blue
            if ($pRandom > 0.7) {
                $color = 'rgba(241, 122, 31, 0.22)'; // Soft Orange
            } else if ($pRandom > 0.4) {
                $color = 'rgba(43, 147, 34, 0.22)'; // Soft Green
            }
            
            $x = $pRandom * 100;
            $y = (sin($i * 12345.67) * 0.5 + 0.5) * 100;
            $size = (sin($i * 543.21) * 0.5 + 0.5) * 2.8 + 1.2; // 1.2px to 4px
            $delay = (sin($i * 987.65) * 0.5 + 0.5) * 8;
            $duration = (sin($i * 135.79) * 0.5 + 0.5) * 14 + 12; // 12s to 26s
        ?>
            <div 
                class="absolute rounded-full" 
                style="
                    position: absolute;
                    left: <?= $x ?>%; 
                    top: <?= $y ?>%; 
                    width: <?= $size ?>px; 
                    height: <?= $size ?>px; 
                    background-color: <?= $color ?>; 
                    box-shadow: 0 0 <?= $size * 1.5 ?>px <?= $color ?>, 0 0 <?= $size * 3.5 ?>px rgba(255, 255, 255, 0.9);
                    animation: phpTwinkle <?= $duration ?>s infinite ease-in-out;
                    animation-delay: <?= $delay ?>s;
                "
            ></div>
        <?php endfor; ?>
    </div>

    <!-- Sticky Academic Navigation Header -->
    <nav class="sticky top-0 bg-[#0c2340]/95 backdrop-blur-md border-b-[3px] border-st-orange/70 z-40 transition-all shadow-[0_4px_30px_rgba(0,0,0,0.25)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center py-4.5 md:py-0 md:h-20 gap-4 md:gap-0">
                
                <!-- Premium Branded Logo and Title -->
                <div class="flex flex-col sm:flex-row items-center gap-3.5 text-center sm:text-left">
                    <!-- STL Beautiful High Fidelity PNG Logo -->
                    <div class="h-12 w-12 sm:h-13 sm:w-13 shrink-0 flex items-center justify-center bg-white/5 rounded-xl border border-white/10 p-1.5 shadow-sm">
                        <img src="assets/stlab-logo.png?v=3" alt="STLab Logo" class="h-9 sm:h-10.5 w-auto select-none object-contain" />
                    </div>
                    <div class="space-y-0.5">
                        <span class="text-sm sm:text-base font-extrabold text-white tracking-tight block leading-tight drop-shadow-sm">
                            Sustainable Technologies Laboratory
                        </span>
                        <span class="text-[9.5px] sm:text-[10.5px] text-[#ffa34d] font-bold tracking-widest block uppercase font-mono">
                            IIT Indore
                        </span>
                        <span class="text-[9.5px] sm:text-[11px] text-slate-300 font-medium block font-sanskrit tracking-wide leading-tight mt-1">
                            संवहनीय-तान्त्रिकी-गवेषणशाला (भारतीय-प्राद्यौगिकी-संस्थानम् इन्दौरस्थम्)
                        </span>
                    </div>
                </div>

                <!-- Brand/Branding Section remains on the left and utility info on the right -->

                <!-- Right side Elements: IIT Indore Logo & Google Translate -->
                <div class="flex items-center gap-3 sm:gap-4 shrink-0">
                    <!-- IIT Indore Logo -->
                    <a href="https://www.iiti.ac.in/" target="_blank" rel="noopener noreferrer" class="block hover:scale-105 transition-transform" title="Indian Institute of Technology Indore">
                        <img src="assets/iiti_logo.jpg" alt="IIT Indore Logo" class="h-9 sm:h-10.5 w-auto object-contain bg-white p-1 rounded-lg border border-slate-200/20 shadow-sm">
                    </a>

                    <!-- Google Translate Element -->
                    <div id="google_translate_element" class="google-translate-container text-xs hidden sm:block"></div>
                </div>

            </div>
        </div>
    </nav>

    <!-- Main Content Container serving our Central Tab Hub Deck -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full relative z-10" style="position: relative; z-index: 10;">
        
        <div class="bg-white rounded-2xl border border-slate-200 shadow-[0_12px_24px_-8px_rgba(16,76,122,0.08)] overflow-hidden">
            
            <!-- Campus Brand Header Banner -->
            <div class="bg-gradient-to-r from-[#0c2340] to-[#0e4a7c] border-b-2 border-st-orange/40 px-6 sm:px-10 py-10 relative overflow-hidden text-white">
                <div class="absolute right-0 top-0 w-64 h-64 bg-st-orange/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div class="space-y-1.5 w-full">
                        <span class="text-st-orange font-mono text-[10px] font-bold uppercase tracking-[0.25em] block animate-pulse">
                            Research & Process Engineering Core • Indian Institute of Technology Indore
                        </span>
                        <h2 class="text-3xl font-extrabold tracking-tight">
                            Sustainable Technologies Laboratory
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Hub Quick Navigation Row -->
            <div class="bg-slate-100/90 border-b border-slate-200 px-4 flex flex-wrap gap-1 md:gap-3 py-2 shadow-inner">
                <button onclick="switchTab('home')" id="tab-btn-home" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-blue">
                    <i data-lucide="home" class="h-3.5 w-3.5 text-st-blue"></i>
                    Home
                </button>
                <button onclick="switchTab('news')" id="tab-btn-news" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-amber-600">
                    <i data-lucide="newspaper" class="h-3.5 w-3.5 text-amber-600"></i>
                    News & Announcements
                </button>
                <button onclick="switchTab('research')" id="tab-btn-research" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-orange">
                    <i data-lucide="cpu" class="h-3.5 w-3.5 text-st-orange"></i>
                    Research Areas
                </button>
                <button onclick="switchTab('people')" id="tab-btn-people" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-green">
                    <i data-lucide="users" class="h-3.5 w-3.5 text-st-green"></i>
                    Lab Members
                </button>
                <button onclick="switchTab('publications')" id="tab-btn-publications" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-blue">
                    <i data-lucide="book-open" class="h-3.5 w-3.5 text-st-blue"></i>
                    Key Publications
                </button>
                <button onclick="switchTab('learning')" id="tab-btn-learning" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-green">
                    <i data-lucide="graduation-cap" class="h-3.5 w-3.5 text-st-green"></i>
                    Learning Resources
                </button>
                <button onclick="switchTab('gallery')" id="tab-btn-gallery" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-blue">
                    <i data-lucide="camera" class="h-3.5 w-3.5 text-st-blue"></i>
                    Lab Gallery
                </button>
                <button onclick="switchTab('weather')" id="tab-btn-weather" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-green">
                    <i data-lucide="cloud-sun" class="h-3.5 w-3.5 text-st-green"></i>
                    IITI Weather Statistics
                </button>
                <button onclick="switchTab('contact')" id="tab-btn-contact" class="flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-orange">
                    <i data-lucide="phone" class="h-3.5 w-3.5 text-st-orange"></i>
                    Contact Lab
                </button>
            </div>

            <!-- Interactive View panels -->
            <div class="p-6 sm:p-8 min-h-[350px]">
                
                <!-- WELCOME PANE -->
                <div id="pane-home" class="tab-pane hidden animate-tabFadeIn relative p-6 sm:p-10 rounded-2xl overflow-hidden border border-slate-200/50 shadow-xs bg-white">
                    <!-- STL Logo Theme Soft Glowing Accent Circles -->
                    <div class="absolute -top-12 -right-12 w-64 h-64 bg-st-blue/3 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute -bottom-16 -left-16 w-80 h-80 bg-st-orange/3 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute top-1/2 left-1/4 w-48 h-48 bg-st-green/2 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="relative z-10 space-y-8 w-full">
                        <div class="bg-gradient-to-r from-st-blue/5 via-st-orange/10 to-st-blue/5 border border-st-orange/20 p-6 sm:p-7 rounded-2xl space-y-4 shadow-sm backdrop-blur-sm flex flex-col items-center justify-center text-center">
                        <h3 class="text-xl sm:text-2xl font-extrabold text-[#0c2340] tracking-tight leading-snug">
                            Bridging Systems Engineering and Traditional Knowledge
                        </h3>
                        
                        <!-- Sanskrit Shloka Card -->
                        <div class="py-4.5 px-6 bg-white/95 rounded-xl border border-st-orange/15 shadow-sm max-w-3xl w-full space-y-3 mx-auto">
                            <p class="text-base sm:text-lg font-bold text-st-orange tracking-wide text-center leading-relaxed font-sanskrit">
                                काले वर्षतु पर्जन्य≍पृथिवी सस्यशालिनी।<br />
                                देशोऽयं क्षोभरहितो ब्राह्मणाः सन्तु निर्भयाः॥
                            </p>
                            <div class="h-px bg-gradient-to-r from-transparent via-slate-200 to-transparent w-full"></div>
                            <p class="text-xs sm:text-sm text-slate-700 italic text-center font-medium max-w-2xl mx-auto leading-relaxed">
                                "May the rains come on time, the earth be verdant, this country be free of distress and intellectuals be fearless."
                            </p>
                        </div>
                    </div>

                    <!-- Highlighting Flowing Announcements Ticker -->
                    <div class="bg-white border border-slate-200 rounded-xl shadow-xs overflow-hidden flex flex-col md:flex-row items-stretch">
                        <div class="bg-[#0c2340] text-white px-5 py-3 flex items-center gap-2 font-sans font-extrabold text-xs uppercase tracking-wider shrink-0 select-none border-b md:border-b-0 md:border-r border-slate-700 z-10">
                            <i data-lucide="megaphone" class="h-3.5 w-3.5 text-st-orange animate-bounce"></i>
                            <span>Recent Highlights</span>
                            <span class="hidden md:inline h-1.5 w-1.5 rounded-full bg-st-green"></span>
                        </div>
                        <div class="flex-1 overflow-hidden py-3 relative bg-slate-50/50 flex items-center">
                            <div class="animate-marquee whitespace-nowrap flex items-center gap-12 text-xs font-semibold text-slate-700 select-none" style="display: flex; width: max-content; gap: 3rem; animation: marquee 35s linear infinite;">
                                <span class="flex items-center gap-2">
                                    <i data-lucide="award" class="h-4 w-4 text-st-orange shrink-0 animate-pulse"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-orange/10 text-st-orange px-1.5 py-0.5 rounded mr-1">Global Ranking</strong> Prof. Ganti S. Murthy has been ranked among the top 2% of scientists worldwide by Stanford University!</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="award" class="h-4 w-4 text-st-green shrink-0"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-green/10 text-st-green px-1.5 py-0.5 rounded mr-1">Fellowship</strong> Mr. Mohanchaitanya Reddy selected for the prestigious national Adani Indological Fellowship!</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="graduation-cap" class="h-4 w-4 text-st-blue shrink-0"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-blue/10 text-st-blue px-1.5 py-0.5 rounded mr-1">Chair</strong> Prof. Ganti S. Murthy serves as the prestigious Adani Indology Chair Professor (Agriculture) at IIT Indore!</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="sparkles" class="h-4 w-4 text-st-orange shrink-0"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-orange/10 text-st-orange px-1.5 py-0.5 rounded mr-1">UIUC Outbound</strong> Ms. Kavita Singh has been selected for the collaborative research fellowship at UIUC, USA!</span>
                                </span>

                                <!-- DUPLICATE TO RESEMBLE INFINITE SCROLL -->
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="award" class="h-4 w-4 text-st-orange shrink-0 animate-pulse"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-orange/10 text-st-orange px-1.5 py-0.5 rounded mr-1">Global Ranking</strong> Prof. Ganti S. Murthy has been ranked among the top 2% of scientists worldwide by Stanford University!</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="award" class="h-4 w-4 text-st-green shrink-0"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-green/10 text-st-green px-1.5 py-0.5 rounded mr-1">Fellowship</strong> Mr. Mohanchaitanya Reddy selected for the prestigious national Adani Indological Fellowship!</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="graduation-cap" class="h-4 w-4 text-st-blue shrink-0"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-blue/10 text-st-blue px-1.5 py-0.5 rounded mr-1">Chair</strong> Prof. Ganti S. Murthy serves as the prestigious Adani Indology Chair Professor (Agriculture) at IIT Indore!</span>
                                </span>
                                <span class="text-slate-300">|</span>
                                <span class="flex items-center gap-2">
                                    <i data-lucide="sparkles" class="h-4 w-4 text-st-orange shrink-0"></i>
                                    <span><strong class="text-slate-900 uppercase tracking-wider text-[10px] font-extrabold bg-st-orange/10 text-st-orange px-1.5 py-0.5 rounded mr-1">UIUC Outbound</strong> Ms. Kavita Singh has been selected for the collaborative research fellowship at UIUC, USA!</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start pt-2">
                        <!-- Left perspective block -->
                        <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <div class="space-y-2.5">
                                <h4 class="text-sm font-extrabold text-[#0c2340] uppercase tracking-wider flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-st-orange inline-block"></span>
                                    Systems Perspective & Sustainable Technologies
                                </h4>
                                <p class="text-slate-700 leading-relaxed text-xs sm:text-[13px]">
                                    We must address the challenges of meeting the increasing demands for water, food, and energy in a resource-constrained world subjected to climate variations. STL's research is built on a systems perspective that evaluates the nexus of technology, economics, the environment, and policy. We focus on understanding these linkages to develop sustainable technologies and resilient strategies.
                                </p>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 sm:p-7 space-y-3.5 mt-5 shadow-inner">
                                <span class="text-[10px] uppercase tracking-wide text-st-blue font-extrabold block">Current Sponsoring & Funding partners</span>
                                <div class="flex flex-col gap-2 text-xs font-semibold text-slate-600">
                                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs">
                                        <span class="h-1.5 w-1.5 rounded-full bg-st-green shrink-0"></span>
                                        <span>Department of Biotechnology (DBT), Govt. of India</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs">
                                        <span class="h-1.5 w-1.5 rounded-full bg-st-orange shrink-0"></span>
                                        <span>Ministry of Education (MoE), India</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs">
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-400 shrink-0"></span>
                                        <span>University Grant Commission (UGC)</span>
                                    </div>
                                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-2xs">
                                        <span class="h-1.5 w-1.5 rounded-full bg-st-blue shrink-0"></span>
                                        <span>Adani Foundation, India</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right direct link elements -->
                        <div class="space-y-4 text-xs sm:text-sm text-slate-600 leading-relaxed">
                            <div class="space-y-2.5">
                                <h4 class="text-sm font-extrabold text-[#0c2340] uppercase tracking-wider flex items-center gap-2">
                                    <span class="h-2.5 w-2.5 rounded-full bg-st-green inline-block"></span>
                                    Indian Knowledge Systems (IKS) & TEK
                                </h4>
                                <p class="text-slate-700 leading-relaxed text-xs sm:text-[13px]">
                                    Unique to our lab is the integration of Indian Knowledge Systems (IKS) and Traditional Ecological Knowledge (TEK). By drawing upon the Indian scientific knowledge tradition—including Indian astronomy and traditional agriculture—we bridge ancient predictive frameworks with modern nexus analysis to pioneer new pathways in rural development and technology.
                                </p>
                            </div>

                            <!-- Academic PI Capsule Box -->
                            <div class="bg-[#0c2340] rounded-xl p-6 sm:p-7.5 text-white flex flex-col justify-between relative overflow-hidden shadow-md mt-5 min-h-40">
                                <div class="space-y-3.5 z-10 relative">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider bg-st-orange text-white">
                                        Principal Investigator
                                    </span>
                                    <div class="space-y-0.5">
                                        <h4 class="text-base font-extrabold tracking-tight">Prof. Ganti S. Murthy</h4>
                                        <p class="text-[11px] text-[#ffa34d] font-bold">Professor & Principal Investigator</p>
                                    </div>
                                    <p class="text-[11px] text-slate-300 leading-relaxed font-light">
                                        Professor at IITI BSBE Department, former tenured faculty member at Oregon State University, USA, spearheading food-energy-water nexus sustainability study cores.
                                    </p>
                                </div>
                                <div class="border-t border-white/10 pt-3 mt-3 text-[9px] text-slate-400 font-mono flex items-center gap-1.5 shrink-0">
                                    <i data-lucide="map-pin" class="h-3 w-3 text-st-orange"></i>
                                    <span>Helium Building (POD 1C), IIT Indore</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Useful Links Section -->
                    <div class="border-t border-slate-200/60 pt-8 mt-4.5">
                        <h4 class="text-sm font-extrabold text-[#0c2340] uppercase tracking-wider flex items-center gap-2 mb-5">
                            <span class="h-2.5 w-2.5 rounded-full bg-st-blue inline-block animate-pulse"></span>
                            Useful & Affiliated Links
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <a href="https://www.iiti.ac.in/" target="_blank" rel="noopener noreferrer" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col justify-between hover:border-st-orange/60 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group cursor-pointer">
                                <div class="space-y-2">
                                    <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center text-st-orange border border-orange-100">
                                        <i data-lucide="globe" class="h-4 w-4"></i>
                                    </div>
                                    <h5 class="text-[12px] font-bold text-slate-800 tracking-tight group-hover:text-st-orange transition-colors">
                                        IIT Indore (IITI)
                                    </h5>
                                    <p class="text-[11px] text-slate-500 leading-normal font-sans">
                                        The official main web portal of the Indian Institute of Technology Indore.
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] font-bold text-slate-450 mt-4 font-mono uppercase tracking-wider">
                                    <span>Visit Website</span>
                                    <i data-lucide="external-link" class="h-3 w-3 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                </div>
                            </a>

                            <a href="https://bsbe.iiti.ac.in/" target="_blank" rel="noopener noreferrer" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col justify-between hover:border-st-green/60 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group cursor-pointer">
                                <div class="space-y-2">
                                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-st-green border border-green-100">
                                        <i data-lucide="cpu" class="h-4 w-4"></i>
                                    </div>
                                    <h5 class="text-[12px] font-bold text-slate-800 tracking-tight group-hover:text-st-green transition-colors">
                                        IITI BSBE Department
                                    </h5>
                                    <p class="text-[11px] text-slate-500 leading-normal font-sans">
                                        Department of Biosciences and Biomedical Engineering at IIT Indore.
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] font-bold text-slate-450 mt-4 font-mono uppercase tracking-wider">
                                    <span>Visit Department</span>
                                    <i data-lucide="external-link" class="h-3 w-3 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                </div>
                            </a>

                            <a href="https://crdt.iiti.ac.in/" target="_blank" rel="noopener noreferrer" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col justify-between hover:border-st-orange/60 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group cursor-pointer">
                                <div class="space-y-2">
                                    <div class="w-8 h-8 rounded-lg bg-sky-50 flex items-center justify-center text-st-blue border border-sky-100">
                                        <i data-lucide="home" class="h-4 w-4"></i>
                                    </div>
                                    <h5 class="text-[12px] font-bold text-slate-800 tracking-tight group-hover:text-st-blue transition-colors">
                                        Centre for Rural Dev. & Tech
                                    </h5>
                                    <p class="text-[11px] text-slate-500 leading-normal font-sans">
                                        Promoting application-driven solutions and micro-technologies for rural development.
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] font-bold text-slate-450 mt-4 font-mono uppercase tracking-wider">
                                    <span>Visit Center</span>
                                    <i data-lucide="external-link" class="h-3 w-3 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                </div>
                            </a>

                            <a href="https://cisks.iiti.ac.in/" target="_blank" rel="noopener noreferrer" class="bg-white border border-slate-200 rounded-xl p-4 flex flex-col justify-between hover:border-amber-500/60 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group cursor-pointer">
                                <div class="space-y-2">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 border border-amber-100">
                                        <i data-lucide="book-open" class="h-4 w-4"></i>
                                    </div>
                                    <h5 class="text-[12px] font-bold text-slate-800 tracking-tight group-hover:text-amber-600 transition-colors">
                                        Centre for IKS (CISKS)
                                    </h5>
                                    <p class="text-[11px] text-slate-500 leading-normal font-sans">
                                        Centre for Indian Knowledge System at IIT Indore, bridging science, culture and tradition.
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-[10px] font-bold text-slate-450 mt-4 font-mono uppercase tracking-wider">
                                    <span>Visit Center</span>
                                    <i data-lucide="external-link" class="h-3 w-3 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Foot maps redirection links -->
                    <div class="border-t border-slate-200 pt-5 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-[#0c2340]/5 rounded-lg border border-[#0c2340]/10">
                                <i data-lucide="map-pin" class="h-4 w-4 text-st-orange animate-bounce"></i>
                            </div>
                            <div class="space-y-0.5 text-left">
                                <p class="text-xs font-bold text-slate-800">Visit Sustainable Technologies Laboratory at IIT Indore</p>
                                <p class="text-[10px] text-slate-500 font-mono">Khandwa Road, Simrol Campus, Indore, MP 453552</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="switchTab('contact')" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-[#0c2340] bg-[#0c2340]/5 hover:bg-[#0c2340]/10 border border-[#0c2340]/10 rounded-lg transition-all cursor-pointer">
                                View Floor Map
                                <i data-lucide="arrow-right" class="h-3 w-3"></i>
                            </button>
                            <a href="https://maps.app.goo.gl/EP3dxc4qz6pxHgyaA" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-[#0c2340] hover:bg-[#ffa04d] hover:text-[#0c2340] text-white rounded-lg transition-all shadow-md cursor-pointer transform hover:-translate-y-0.5">
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                Google Maps Route
                            </a>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- NEWS & ANNOUNCEMENTS PANE -->
                <div id="pane-news" class="tab-pane hidden space-y-8 animate-tabFadeIn">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-st-blue">
                            <span class="p-1 px-2.5 bg-st-blue/10 text-st-blue text-[10px] font-bold uppercase rounded-md tracking-wider border border-st-blue/20">
                                Bulletin Board
                            </span>
                            <span class="h-1.5 w-1.5 rounded-full bg-st-orange inline-block"></span>
                            <span class="text-xs text-slate-500 font-medium">Updates & Milestones</span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold uppercase tracking-tight text-[#0c2340]">
                            News & Announcements
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 font-sans font-light max-w-3xl">
                            Stay updated with the latest achievements, fellowships, publications, and career milestones of the members at Sustainable Technologies Laboratory (STLab), IIT Indore.
                        </p>
                    </div>

                    <!-- Sub-tabs for news filtering: Active vs Archive -->
                    <div class="flex border-b border-slate-200 gap-1 sm:gap-2">
                        <button 
                            id="news-subtab-active"
                            onclick="switchNewsSubTab('active')"
                            class="flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-st-orange text-[#0c2340] font-extrabold"
                        >
                            <span>Recent Announcements</span>
                            <span class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-st-orange indicator"></span>
                        </button>
                        <button 
                            id="news-subtab-archive"
                            onclick="switchNewsSubTab('archive')"
                            class="flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-transparent text-slate-400 hover:text-slate-600 font-medium"
                        >
                            <span>Archive (2024/2025)</span>
                            <span class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-transparent indicator"></span>
                        </button>
                    </div>

                     <!-- List of Announcements with premium styling - Active Announcements -->
                    <div id="news-container-active" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn">

                        <!-- News: Best Idea Presentation Competition 2026 -->
                        <div class="bg-gradient-to-r from-st-orange/5 to-white border-2 border-st-orange/60 rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-28 w-28 bg-gradient-to-br from-st-orange/10 to-transparent rounded-bl-full pointer-events-none"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start">
                                <div class="p-3 bg-st-orange/10 rounded-xl text-st-orange shrink-0 border border-st-orange/20 flex items-center justify-center shadow-inner animate-pulse">
                                    <i data-lucide="award" class="h-7 w-7"></i>
                                </div>
                                <div class="space-y-3 flex-1 font-sans">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-0.5 bg-st-orange text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                            Best Idea Presentation Winners
                                        </span>
                                        <span class="px-2.5 py-0.5 bg-st-blue text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                            National Youth Day 2026
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-500 font-mono">
                                            12 January 2026
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-lg sm:text-xl font-extrabold text-[#0c2340] leading-snug group-hover:text-st-orange transition-colors">
                                            Best Idea Presentation Competition 2026!
                                        </h4>
                                        <p class="text-xs text-slate-600 mt-1.5 leading-relaxed font-light">
                                            We are proud to share that STL Lab members <span class="font-bold text-slate-800">Abhishek D Kalbande (First Prize)</span> and <span class="font-bold text-slate-800">Buddhodev Ghosh (Joint-Second Prize)</span> were winners at the Best Idea Presentation Competition.
                                        </p>
                                    </div>
                                    <div class="p-4 bg-white/70 border border-st-orange/15 rounded-xl text-xs text-slate-700 leading-relaxed font-light">
                                        The competition was jointly organized by the <span class="font-semibold text-[#0c2340]">Centre for Rural Development and Technology (CRDT)</span> and the <span class="font-semibold text-[#0c2340]">Centre for Indian Scientific Knowledge System (CISKS)</span> at IIT Indore in celebration of <span class="font-bold text-st-blue">National Youth Day (Rashtriya Yuva Diwas)</span> on 12 January 2026. This outstanding double win signals STLab's prominent voice in translational solutions and indigenous science.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- News: Youth for Social Change Summit 2026 -->
                        <div class="bg-white border border-slate-200/80 hover:border-st-orange/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 relative overflow-hidden group flex flex-col justify-between">
                            <div class="absolute top-0 right-0 h-24 w-24 bg-gradient-to-br from-st-orange/5 to-transparent rounded-bl-full pointer-events-none"></div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="p-3 bg-st-blue/5 rounded-xl text-st-blue border border-st-blue/10 flex items-center justify-center font-bold">
                                        <i data-lucide="users" class="h-5 w-5"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-400 font-mono">
                                        Year 2026
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <span class="px-2 py-0.5 bg-st-blue/5 border border-st-blue/20 rounded-md text-[9px] font-bold text-st-blue uppercase tracking-wider">
                                        Social Innovation Laurels
                                    </span>
                                    <h4 class="text-base font-bold text-slate-900 group-hover:text-st-orange transition-colors">
                                        Youth for Social Change Summit 2026
                                    </h4>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans font-light">
                                        We are thrilled to state that <span class="font-bold text-slate-800">Buddhodev Ghosh</span>, representing the <span class="font-semibold text-slate-800">CRDT-IITI team</span>, secured the prestigious <span class="font-bold text-st-orange">Third Runner-Up position</span> in the Grand Finale of the summit held at Devi Ahilyabai University (DAVV), Indore.
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-500 flex items-center gap-1.5 font-sans justify-between">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="sparkles" class="h-3 w-3 text-amber-500"></i>
                                    <span>Awarded with a cash prize.</span>
                                </span>
                            </div>
                        </div>

                        <!-- News: Dagguganta PhD Senator Re-election -->
                        <div class="bg-white border border-slate-200/80 hover:border-st-orange/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 relative overflow-hidden group flex flex-col justify-between">
                            <div class="absolute top-0 right-0 h-24 w-24 bg-gradient-to-br from-st-orange/5 to-transparent rounded-bl-full pointer-events-none"></div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="p-3 bg-st-orange/5 rounded-xl text-st-orange border border-st-orange/10 flex items-center justify-center font-bold">
                                        <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-400 font-mono">
                                        Year 2026
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <span class="px-2 py-0.5 bg-st-orange/5 border border-st-orange/20 rounded-md text-[9px] font-bold text-st-orange uppercase tracking-wider">
                                        Senate Leadership
                                    </span>
                                    <h4 class="text-base font-bold text-slate-900 group-hover:text-st-orange transition-colors">
                                        Institute PhD Senator Re-election
                                    </h4>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans font-light">
                                        Hearty congratulations to <span class="font-bold text-slate-800">Dagguganta Mohan Chaitanya Reddy</span> for being elected as the <span class="font-semibold text-slate-800">Institute PhD Senator</span> at IIT Indore for the <span class="font-bold text-st-blue">second consecutive year</span>!
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-500 flex items-center gap-1.5 font-sans">
                                <i data-lucide="megaphone" class="h-3 w-3 text-amber-500"></i>
                                <span>Selected via direct student franchise to represent doctoral scholars.</span>
                            </div>
                        </div>

                        <!-- News 0: Prof. Ganti S. Murthy - Stanford-Elsevier Top 2% Scientists -->
                        <div class="bg-gradient-to-r from-st-blue/5 via-st-orange/5 to-white border-2 border-st-orange/60 rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-st-orange/10 to-transparent rounded-bl-full pointer-events-none transition-all group-hover:scale-110"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start">
                                <div class="p-3.5 bg-st-orange/10 rounded-xl text-st-orange shrink-0 border border-st-orange/20 flex items-center justify-center shadow-inner animate-float-3d">
                                    <i data-lucide="award" class="h-7 w-7"></i>
                                </div>
                                <div class="space-y-3 flex-1 font-sans">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2.5 py-0.5 bg-st-orange text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                            Global Recognition
                                        </span>
                                        <span class="px-2 py-0.5 bg-st-blue text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                            Stanford-Elsevier List
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-500 font-mono">
                                            Top 2% scientists Worldwide
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-lg sm:text-xl font-extrabold text-[#0c2340] leading-snug group-hover:text-st-orange transition-colors">
                                            Prof. Ganti S. Murthy (Lab PI) Ranked Among Top 2% Scientists Worldwide
                                        </h4>
                                        <p class="text-xs text-slate-605 mt-1.5 leading-relaxed font-light">
                                            We are thrilled to share that our Principal Investigator, <span class="font-bold text-slate-800">Prof. Ganti Suryanarayana Murthy</span>, is featured in the highly prestigious <span class="font-semibold text-[#0c2340]">Stanford-Elsevier ranking</span>, which lists the top 2% of scientists globally based on standardized citation indicators. This is a recognitions of the work of all STL Team members over years.
                                        </p>
                                    </div>
                                    <div class="p-4 bg-white/70 border border-st-orange/15 rounded-xl text-xs text-slate-700 leading-relaxed font-light">
                                        This honors the STLab's decades of research in bioprocess modeling, sustainable technology development, and life-cycle assessments (LCA). It places STLab and IIT Indore at the forefront of global scientific authority, testifying to the rigorous excellence maintained in our laboratory across food-energy-water nexus studies and Indian Knowledge Systems.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- News 1: Mr. Dagguganta Mohanchaitanya Reddy -->
                        <div class="bg-white border border-slate-200/80 hover:border-amber-500/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-full pointer-events-none transition-all group-hover:scale-110"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start">
                                <div class="p-3 bg-amber-50 rounded-xl text-amber-600 shrink-0 border border-amber-100 flex items-center justify-center shadow-inner">
                                    <i data-lucide="award" class="h-6 w-6"></i>
                                </div>
                                <div class="space-y-3 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 rounded-md text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                                            Adani Indological Fellowship
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-400 font-mono">
                                            Class of 2025
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-slate-900 group-hover:text-amber-700 transition-colors">
                                            Congratulations to Mr. Dagguganta Mohanchaitanya Reddy!
                                        </h4>
                                        <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                            Selected for the prestigious <span class="font-semibold text-slate-900">Adani Indological Fellowship</span> — among <span class="font-bold text-amber-700">only 14 scholars selected across India</span>.
                                        </p>
                                    </div>
                                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-700 leading-relaxed font-sans font-light">
                                        This highly selective national recognition was awarded for his outstanding research and work on <span class="font-bold text-[#0c2340]">Herbal Kunapajala</span> (a traditional bio-fertilizer formulation in Vrikshayurveda science). His work explores botanical growth promoters to propose ecological, chemical-free farming techniques.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- News 2: Ms. Kavita Singh -->
                        <div class="bg-white border border-slate-200/80 hover:border-st-orange/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 relative overflow-hidden group flex flex-col justify-between">
                            <div class="absolute top-0 right-0 h-24 w-24 bg-gradient-to-br from-st-orange/5 to-transparent rounded-bl-full pointer-events-none"></div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="p-3 bg-st-orange/5 rounded-xl text-st-orange border border-st-orange/10 flex items-center justify-center">
                                        <i data-lucide="globe" class="h-5 w-5"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-400 font-mono">
                                        Year 2026
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <span class="px-2 py-0.5 bg-st-orange/5 border border-st-orange/20 rounded-md text-[9px] font-bold text-st-orange uppercase tracking-wider">
                                        Collaborative Research Fellowship
                                    </span>
                                    <h4 class="text-base font-bold text-slate-900 group-hover:text-st-orange transition-colors">
                                        Congratulations to Ms. Kavita Singh
                                    </h4>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans font-light">
                                        Selected for the prestigious <span class="font-semibold text-slate-800">Short-term Collaborative Research Fellowship (Outbound PhD)</span> at the <span class="font-bold text-st-blue">University of Illinois Urbana-Champaign</span>, USA.
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-500 flex items-center gap-1.5 font-sans">
                                <i data-lucide="sparkles" class="h-3 w-3 text-amber-500 animate-pulse"></i>
                                <span>Enabling international research scaling and collaboration.</span>
                            </div>
                        </div>

                        <!-- News 3: Mr. Abhishek D Kalbande -->
                        <div class="bg-white border border-slate-200/80 hover:border-st-green/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 relative overflow-hidden group flex flex-col justify-between">
                            <div class="absolute top-0 right-0 h-24 w-24 bg-gradient-to-br from-st-green/5 to-transparent rounded-bl-full pointer-events-none"></div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-start">
                                    <div class="p-3 bg-st-green/5 rounded-xl text-st-green border border-st-green/10 flex items-center justify-center">
                                        <i data-lucide="briefcase" class="h-5 w-5"></i>
                                    </div>
                                    <span class="text-[11px] font-bold text-slate-400 font-mono">
                                        Year 2026
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <span class="px-2 py-0.5 bg-st-green/5 border border-st-green/20 rounded-md text-[9px] font-bold text-st-green uppercase tracking-wider">
                                        Executive Appointment
                                    </span>
                                    <h4 class="text-base font-bold text-slate-900 group-hover:text-st-green transition-colors">
                                        Congratulations to Mr. Abhishek D Kalbande
                                    </h4>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans font-light">
                                        Selected as a <span class="font-semibold text-slate-800">Junior Executive Officer</span> at <span class="font-bold text-st-blue">Symbiotec Zenfold Pvt. Ltd, Ujjain (M.P.)</span>.
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-slate-100 mt-4 text-[11px] text-slate-500 flex items-center gap-1.5 font-sans">
                                <i data-lucide="trending-up" class="h-3 w-3 text-st-green"></i>
                                <span>Bridging academic excellence with industrial leadership opportunities.</span>
                            </div>
                        </div>

                        <!-- News 5: New Research Publication (Hexadic Tank) -->
                        <div class="bg-white border border-slate-200/80 hover:border-st-blue/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-st-blue/5 to-transparent rounded-bl-full pointer-events-none"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start">
                                <div class="p-3 bg-st-blue/5 rounded-xl text-st-blue shrink-0 border border-st-blue/10 flex items-center justify-center shadow-inner">
                                    <i data-lucide="book-open" class="h-6 w-6"></i>
                                </div>
                                <div class="space-y-3 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 bg-st-blue/5 border border-st-blue/10 rounded-md text-[10px] font-bold text-st-blue uppercase tracking-wider">
                                            New Research Publication Update
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-400 font-mono">
                                            Published 2026
                                        </span>
                                    </div>
                                    <div class="space-y-1">
                                        <span class="text-xs text-slate-400 block font-mono font-bold uppercase tracking-wider">Research Article</span>
                                        <h4 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-st-blue transition-colors">
                                            Design and Analysis of a Hexadic Tank System: Classical and Advanced Control Algorithms
                                        </h4>
                                        <p class="text-xs text-[#0c2340] font-semibold">
                                            Journal: <span class="font-semibold text-slate-800">Systems Microbiology and Biomanufacturing</span>
                                        </p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-4 border-t border-slate-100">
                                        <div class="text-xs text-slate-500">
                                            Authored by: <span class="font-bold text-slate-700">Sagnik Mitra</span> and <span class="font-bold text-slate-700">Prof. Ganti S. Murthy</span>
                                        </div>
                                        <a 
                                            href="https://www.researchgate.net/publication/403855681_Design_and_Analysis_of_a_Hexadic_Tank_System_Classical_and_Advanced_Control_Algorithms"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="px-3 py-1 bg-slate-100 hover:bg-[#0c2340] hover:text-white transition-all text-[10.5px] font-bold rounded-lg text-slate-600 border border-slate-200 cursor-pointer flex items-center gap-1"
                                        >
                                            <span>ResearchGate Link</span>
                                            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- List of Announcements with premium styling - Archive Announcements -->
                    <div id="news-container-archive" class="grid grid-cols-1 md:grid-cols-2 gap-6 animate-fadeIn hidden">

                        <!-- News 4: New Research Publication (Trickle Bed Reactor) - Moved to 2025 Archive -->
                        <div class="bg-white border border-slate-200/80 hover:border-amber-500/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-full pointer-events-none transition-all group-hover:scale-110"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start">
                                <div class="p-3 bg-amber-50 rounded-xl text-amber-600 shrink-0 border border-amber-100 flex items-center justify-center shadow-inner">
                                    <i data-lucide="book-open" class="h-6 w-6"></i>
                                </div>
                                <div class="space-y-3 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 rounded-md text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                                            New Research Journal Publication
                                        </span>
                                        <span class="text-[11px] font-bold text-slate-400 font-mono">
                                            Published August 2025
                                        </span>
                                    </div>
                                    <div class="space-y-1">
                                        <span class="text-xs text-slate-400 block font-mono font-bold uppercase tracking-wider">Research Article</span>
                                        <h4 class="text-base sm:text-lg font-bold text-slate-900 leading-snug group-hover:text-amber-700 transition-colors">
                                            Design, construction and evaluation of an innovative horizontal trickle bed reactor for intensification of pectinase production process
                                        </h4>
                                        <p class="text-xs text-[#0c2340] font-semibold">
                                            Journal: <span class="font-semibold text-slate-800">Chemical Engineering and Processing - Process Intensification</span> (Volume 214, 110343)
                                        </p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 pt-4 border-t border-slate-100">
                                        <div class="text-xs text-slate-500">
                                            Authored by: <span class="font-bold text-slate-700">Kavita Singh</span> and <span class="font-bold text-slate-700">Prof. Ganti S. Murthy</span>
                                        </div>
                                        <a 
                                            href="https://www.sciencedirect.com/science/article/pii/S0255270125001928"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="px-3 py-1 bg-slate-100 hover:bg-[#0c2340] hover:text-white transition-all text-[10.5px] font-bold rounded-lg text-slate-600 border border-slate-200 cursor-pointer flex items-center gap-1"
                                        >
                                            <span>ScienceDirect Link</span>
                                            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- News New Archived: Buddhodev Ghosh Presentation Award at Adani Global Indology Conclave -->
                        <div class="bg-white border border-slate-200/80 hover:border-amber-500/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-full pointer-events-none transition-all group-hover:scale-110"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start font-sans">
                                <div class="p-3 bg-amber-50 rounded-xl text-amber-600 shrink-0 border border-amber-100 flex items-center justify-center shadow-inner animate-float-3d">
                                    <i data-lucide="award" class="h-6 w-6"></i>
                                </div>
                                <div class="space-y-3 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 rounded-md text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                                            Poster Presentation Award
                                        </span>
                                        <span class="px-2.5 py-0.5 bg-[#0c2340] text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                            Adani Global Indology Conclave
                                        </span>
                                        <span class="text-[11px] font-semibold text-slate-500 font-mono">
                                            Year 2025 Archive
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-base sm:text-lg font-bold text-slate-900 group-hover:text-amber-700 transition-colors">
                                            Buddhodev Ghosh Secures 3rd Best Presentation in PG-PhD Segment
                                        </h4>
                                        <p class="text-xs text-slate-800 font-semibold mt-1">
                                            Research Work: <span class="italic">"Mathematical Combinatorics in Gandhārṇva Perfume-Making: A Critical Study from Bṛhatsaṃhitā"</span>
                                        </p>
                                    </div>
                                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-600 leading-relaxed font-light">
                                        From approximately <span class="font-semibold text-slate-800">500 national-level entries</span>, Buddhodev Ghosh's innovative mathematical reconstruction of traditional Sanskrit treatises was selected for the finals (under 100 final participants). He successfully pitched his research at the Poster Presentation Competition of the <span class="font-semibold text-slate-800">Adani Global Indology Conclave</span>, winning the prestigious <span class="font-bold text-amber-700">Third-Best Presentation Award</span> in the competitive PG-PhD category.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- News Archived: Mr. Abhishek D. Kalbande Merit Cum Means Scholarship -->
                        <div class="bg-white border border-slate-200/80 hover:border-amber-500/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-full pointer-events-none transition-all group-hover:scale-110"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start font-sans">
                                <div class="p-3 bg-amber-50 rounded-xl text-amber-600 shrink-0 border border-amber-100 flex items-center justify-center shadow-inner animate-float-3d">
                                    <i data-lucide="award" class="h-6 w-6"></i>
                                </div>
                                <div class="space-y-3 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 rounded-md text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                                            Merit Cum Means Scholarship
                                        </span>
                                        <span class="px-2.5 py-0.5 bg-[#0c2340] text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider">
                                            Dept. of BSBE, IIT Indore
                                        </span>
                                        <span class="text-[11px] font-semibold text-slate-500 font-mono">
                                            Year 2025 Archive
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-base sm:text-lg font-bold text-slate-900 group-hover:text-amber-700 transition-colors">
                                            Mr. Abhishek D. Kalbande Awarded Merit Cum Means Scholarship
                                        </h4>
                                        <p class="text-xs text-slate-600 leading-relaxed font-sans font-light mt-1.5">
                                            Congratulations to <span class="font-bold text-slate-800">Abhishek D. Kalbande</span> for being selected for the prestigious <span class="font-semibold text-[#0c2340]">Merit Cum Means (MCM) Scholarship</span> awarded by the <span class="font-semibold text-slate-800">Department of Biosciences and Biomedical Engineering (BSBE), IIT Indore</span> in 2025.
                                        </p>
                                    </div>
                                    <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-xs text-slate-600 leading-relaxed font-light">
                                        This merit-based honor reflects academic distinction, dedication to research, and outstanding performance within the Department of BSBE at IIT Indore.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- News Archived: Dagguganta Mohanchaitanya Reddy Elected Head of Biocrats Club -->
                        <div class="bg-white border border-slate-200/80 hover:border-amber-500/50 rounded-2xl p-6 shadow-xs hover:shadow-lg transition-all duration-300 md:col-span-2 relative overflow-hidden group">
                            <div class="absolute top-0 right-0 h-32 w-32 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-full pointer-events-none transition-all group-hover:scale-110"></div>
                            <div class="flex flex-col sm:flex-row gap-5 items-start font-sans">
                                <div class="p-3 bg-amber-50 rounded-xl text-amber-600 shrink-0 border border-amber-100 flex items-center justify-center shadow-inner animate-float-3d">
                                    <i data-lucide="graduation-cap" class="h-6 w-6"></i>
                                </div>
                                <div class="space-y-3 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 rounded-md text-[10px] font-bold text-amber-700 uppercase tracking-wider font-sans">
                                            Club Leadership
                                        </span>
                                        <span class="px-2.5 py-0.5 bg-[#0c2340] text-white rounded-md text-[10px] font-extrabold uppercase tracking-wider font-sans">
                                            Biocrats Club
                                        </span>
                                        <span class="text-[11px] font-semibold text-slate-500 font-mono">
                                            Year 2024 Archive
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-base sm:text-lg font-extrabold text-[#0c2340] group-hover:text-amber-700 transition-colors font-sans">
                                            Dagguganta Mohan Chaitanya Reddy Elected Head of Biocrats Club
                                        </h4>
                                        <p class="text-xs text-slate-600 leading-relaxed font-sans font-light mt-1.5 font-sans">
                                            Heartiest congratulations to <span class="font-semibold text-slate-800">Dagguganta Mohan Chaitanya Reddy</span> for being elected as the Student Coordinator / Head of <span class="font-semibold text-[#0c2340]">Biocrats</span>—the prestigious Departmental Club of Bioscience and Biomedical Engineering (BSBE) at IIT Indore.
                                        </p>
                                    </div>
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pt-4 border-t border-slate-100 mt-2">
                                        <div class="p-3 bg-slate-50 border border-slate-100/50 rounded-xl text-xs text-slate-600 leading-relaxed font-light flex-1 font-sans">
                                            The BSBE Departmental Club <span class="font-semibold text-slate-700">Biocrats</span> fosters innovation, collaborative research, and co-curricular enrichment among the bioscience community at IIT Indore.
                                        </div>
                                        <a 
                                            href="https://biocrats.iiti.ac.in/"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="px-3 py-2 bg-slate-100 hover:bg-[#0c2340] hover:text-white transition-all text-[11px] font-black rounded-lg text-slate-600 border border-slate-200 cursor-pointer flex items-center gap-1 shrink-0 self-end sm:self-center font-sans"
                                        >
                                            <span>Biocrats Website</span>
                                            <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <script>
                        function switchNewsSubTab(mode) {
                            const activeBtn = document.getElementById('news-subtab-active');
                            const archiveBtn = document.getElementById('news-subtab-archive');
                            const activeContainer = document.getElementById('news-container-active');
                            const archiveContainer = document.getElementById('news-container-archive');
                            
                            if (mode === 'active') {
                                activeBtn.className = "flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-st-orange text-[#0c2340] font-extrabold";
                                activeBtn.querySelector('.indicator').className = "absolute bottom-0 left-0 right-0 h-[2.5px] bg-st-orange indicator";
                                
                                archiveBtn.className = "flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-transparent text-slate-400 hover:text-slate-600 font-medium";
                                archiveBtn.querySelector('.indicator').className = "absolute bottom-0 left-0 right-0 h-[2.5px] bg-transparent indicator";
                                
                                activeContainer.classList.remove('hidden');
                                archiveContainer.classList.add('hidden');
                            } else {
                                archiveBtn.className = "flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-st-orange text-[#0c2340] font-extrabold";
                                archiveBtn.querySelector('.indicator').className = "absolute bottom-0 left-0 right-0 h-[2.5px] bg-st-orange indicator";
                                
                                activeBtn.className = "flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-transparent text-slate-400 hover:text-slate-600 font-medium";
                                activeBtn.querySelector('.indicator').className = "absolute bottom-0 left-0 right-0 h-[2.5px] bg-transparent indicator";
                                
                                activeContainer.classList.add('hidden');
                                archiveContainer.classList.remove('hidden');
                            }
                        }
                    </script>
                </div>

                <!-- RESEARCH PANE -->
                <div id="pane-research" class="tab-pane hidden space-y-8 animate-tabFadeIn">
                    <div class="bg-gradient-to-br from-st-blue/5 via-slate-50 to-st-orange/5 border border-slate-200 rounded-2xl p-6 sm:p-7 space-y-4 shadow-sm animate-fadeIn">
                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#0c2340] text-white shadow-xs">
                            Our Research Focus
                        </span>
                        <p class="text-slate-700 leading-relaxed text-xs sm:text-[13px]">
                            We live in a resource-constrained world subjected to uncertain climate variations. We must address the challenges of meeting the increasing demands for water, food, and energy from growing populations within the context of the nutrient-food-energy-water-land nexus. A systems perspective considering the nexus of technology, economics, the environment, and policy is at the heart of our research program.
                        </p>
                        <p class="text-slate-700 leading-relaxed text-xs sm:text-[13px]">
                            Our research is focused on understanding these linkages at the systems level to perform integrated analyses, develop sustainable technologies, and build resilient strategies.
                        </p>

                        <!-- OSU150 YouTube hook box -->
                        <div class="bg-white border-2 border-st-orange/20 rounded-xl p-6 sm:p-7.5 mt-2 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm hover:border-st-orange/40 transition-colors">
                            <div class="flex gap-4 items-center">
                                <div class="h-11 w-11 rounded-lg bg-st-orange/10 flex items-center justify-center border border-st-orange/20 text-st-orange shrink-0">
                                    <i data-lucide="play" class="h-5 w-5 text-st-orange"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-[#0c2340] uppercase tracking-wide">Overview Presentation</h4>
                                    <p class="text-slate-600 text-xs mt-0.5 leading-relaxed">
                                        For a comprehensive summary of our foundational research philosophy, watch our <span class="font-bold text-[#0c2340]">systems approach overview talk</span>.
                                    </p>
                                </div>
                            </div>
                            <a href="https://www.youtube.com/watch?v=b965BddtPkE" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold bg-[#0c2340] hover:bg-[#ffa04d] hover:text-[#0c2340] text-white rounded-lg transition-all shadow-md shrink-0 cursor-pointer">
                                Watch Talk
                                <i data-lucide="external-link" class="h-3 w-3"></i>
                            </a>
                        </div>
                    </div>

                    <div class="space-y-1.5 pt-2">
                        <h3 class="text-lg sm:text-xl font-extrabold text-[#0c2340] tracking-tight uppercase">Research Topics</h3>
                        <p class="text-xs text-slate-500">Pioneering core solutions structured across three major domains of ecological systems research.</p>
                    </div>

                    <!-- Three Pillar Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        
                        <!-- Col 1: Sustainable Tech -->
                        <div class="bg-white border border-slate-200 hover:border-st-orange/30 rounded-2xl p-6 sm:p-8 space-y-4 shadow-sm flex flex-col hover:shadow-md duration-300 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-st-orange/10 text-st-orange rounded-xl border border-st-orange/15 shadow-inner">
                                    <i data-lucide="zap" class="h-5 w-5"></i>
                                </div>
                                <h4 class="text-xs font-extrabold text-[#0c2340] tracking-tight leading-snug">
                                    Sustainable Technologies Development
                                </h4>
                            </div>
                            <div class="h-px bg-slate-100"></div>
                            <ul class="space-y-3.5 flex-1 text-xs text-slate-600">
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-orange/10 text-st-orange flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">1</span>
                                    <span>Design and scaleup of bioprocesses in food, nutraceutical and pharmaceutical sectors.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-orange/10 text-st-orange flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">2</span>
                                    <span>Model, optimize, and control bioprocesses.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-orange/10 text-st-orange flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">3</span>
                                    <span>Develop strategies for high-solid fermentation of pretreated cellulosic biomass.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-orange/10 text-st-orange flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">4</span>
                                    <span>Develop novel strategies for the hydrothermal liquefaction of biomass.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-orange/10 text-st-orange flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">5</span>
                                    <span>Evaluate the use of microbial consortia in bioremediation applications.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Col 2: Engineering Systems -->
                        <div class="bg-white border border-slate-200 hover:border-st-blue/30 rounded-2xl p-6 sm:p-8 space-y-4 shadow-sm flex flex-col hover:shadow-md duration-300 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-st-blue/10 text-st-blue rounded-xl border border-st-blue/15 shadow-inner">
                                    <i data-lucide="trending-up" class="h-5 w-5"></i>
                                </div>
                                <h4 class="text-xs font-extrabold text-[#0c2340] tracking-tight leading-snug">
                                    Engineered Systems Analysis
                                </h4>
                            </div>
                            <div class="h-px bg-slate-100"></div>
                            <ul class="space-y-3.5 flex-1 text-xs text-slate-600">
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">1</span>
                                    <span>Engineer and analyze resilient agroecological systems.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">2</span>
                                    <span>Conduct comprehensive, integrated techno-economic analyses (TEA) and life cycle assessments (LCA) of biofuels and bioproducts.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">3</span>
                                    <span>Understand the resilience of global food networks through systems analysis.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">4</span>
                                    <span>Identify metabolic network bottlenecks for xylose utilization in Saccharomyces cerevisiae.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">5</span>
                                    <span>Quantify and understand key sources of uncertainty in the LCA of biofuels and bioproducts.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">6</span>
                                    <span>Perform realistic assessments of bioprocess technologies under diverse future scenarios.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-blue/10 text-st-blue flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">7</span>
                                    <span>Study carbon partitioning in algae under various growth regimes, environmental conditions, and nutrient profiles.</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Col 3: IKS & Rural Tech -->
                        <div class="bg-white border border-slate-200 hover:border-st-green/30 rounded-2xl p-6 sm:p-8 space-y-4 shadow-sm flex flex-col hover:shadow-md duration-300 transition-all">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 bg-st-green/10 text-st-green rounded-xl border border-st-green/15 shadow-inner">
                                    <i data-lucide="sprout" class="h-5 w-5"></i>
                                </div>
                                <h4 class="text-xs font-extrabold text-[#0c2340] tracking-tight leading-snug">
                                    Indian Knowledge Systems
                                </h4>
                            </div>
                            <div class="h-px bg-slate-100"></div>
                            <ul class="space-y-3.5 flex-1 text-xs text-slate-600">
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-green/10 text-st-green flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">1</span>
                                    <span>Bridge traditional Indian agricultural practices with modern agroecological engineering to enhance agro-ecological system health, food and water security and resilience.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-green/10 text-st-green flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">2</span>
                                    <span>Analyze the Indian scientific knowledge tradition, integrating principles from Indian astronomy and historical meteorological methods into contemporary analytical frameworks.</span>
                                </li>
                                <li class="flex gap-2.5 items-start">
                                    <span class="h-5 w-5 rounded-md bg-st-green/10 text-st-green flex items-center justify-center font-bold text-[10px] shrink-0 mt-0.5">3</span>
                                    <span>Investigate traditional ecological knowledge (TEK) in Indian Knowledge Systems (IKS) to inform modern climate resilience and resource management.</span>
                                </li>
                            </ul>
                        </div>

                    </div>

                    <!-- Current Projects Section -->
                    <div class="space-y-4 pt-6 mt-4 border-t border-slate-200">
                        <div class="space-y-1.5">
                            <h3 class="text-lg sm:text-xl font-extrabold text-[#0c2340] tracking-tight uppercase">Current Projects</h3>
                            <p class="text-xs text-slate-500 font-sans">Active research investigations, theoretical modeling, and experimental trials currently underway at STLab.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-st-green/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-st-green/10 text-st-green rounded-lg border border-st-green/15">
                                            <i data-lucide="sprout" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Plant-microbiome-soil</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Investigating and standardising the preparation of Kunapajala (a traditional Indian liquid bio-fertiliser) to enhance sustainable agricultural practices.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-st-orange/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-st-orange/10 text-st-orange rounded-lg border border-st-orange/15">
                                            <i data-lucide="cpu" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Bioprocess scaleup and control</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Designing, modeling, and optimizing bioreactor systems for advanced bioprocessing and sustainable technology applications.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-st-blue/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-st-blue/10 text-st-blue rounded-lg border border-st-blue/15">
                                            <i data-lucide="atom" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Pest management using IKS approaches</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Formulating and evaluating traditional, plant-based pesticide formulations for eco-friendly and sustainable pest management.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-slate-300 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-slate-100 text-slate-600 rounded-lg border border-slate-200">
                                            <i data-lucide="layers" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Toxicity of non-stick cookware under Indian cooking conditions</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Analysis, Characterization and In-Vitro Toxicity Evaluation of By-products from Teflon Coated Cookware Under Cooking Temperature Conditions.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-sky-300 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-sky-50 text-sky-600 rounded-lg border border-sky-100">
                                            <i data-lucide="database" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Hexadic Tank Modeling</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Developing and implementing advanced process control strategies and theoretical simulations utilising an hexadic tank system.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-amber-300 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-amber-50 text-amber-600 rounded-lg border border-amber-100">
                                            <i data-lucide="globe" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Rainfall Models of Bharat</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Analysing algorithmic and observational frameworks in Indian Knowledge Systems (IKS) for understanding and improving the rainfall prediction methods.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-emerald-300 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg border border-emerald-100">
                                            <i data-lucide="sliders" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">Design and fabrication of low-cost automated FPLC system</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Integrating low-cost fluidics, custom microcontrollers, and automated valve control to build a high-performance, budget-friendly FPLC unit.
                                    </p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-white to-slate-50/50 border border-slate-200 hover:border-rose-300 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between hover:-translate-y-0.5 duration-300">
                                <div class="space-y-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="p-2 bg-rose-50 text-rose-600 rounded-lg border border-rose-100">
                                            <i data-lucide="activity" class="h-4.5 w-4.5"></i>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-tight">IR-Based Glucose Sensing and Closed-Loop Insulin Delivery System</h4>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed font-sans">
                                        Design and development of a single-site dual-port minimally invasive device for continuous glucose monitoring and a closed-loop system for personalised insulin delivery pump.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PEOPLE PANE -->
                <div id="pane-people" class="tab-pane hidden space-y-8 animate-tabFadeIn">
                    
                    <div class="space-y-2">
                        <h3 class="text-lg font-bold text-slate-800">Sustainable Technologies Lab Roster</h3>
                        <p class="text-xs text-slate-500">List of researchers, Principal Investigator, and alumni of STLab.</p>
                    </div>

                    <!-- Parallel sub-tabs: Principal Investigator VS Active Researchers VS STLab Alumni -->
                    <div class="flex border-b border-slate-200 gap-1 sm:gap-2">
                        <button 
                            id="people-subtab-pi"
                            onclick="switchPeopleSubTab('pi')"
                            class="flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-st-orange text-[#0c2340] font-extrabold"
                        >
                            <span>Principal Investigator (PI)</span>
                            <span class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-st-orange indicator"></span>
                        </button>
                        <button 
                            id="people-subtab-scholars"
                            onclick="switchPeopleSubTab('scholars')"
                            class="flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-transparent text-slate-400 hover:text-slate-600 font-medium"
                        >
                            <span>Active Researchers</span>
                            <span class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-transparent indicator"></span>
                        </button>
                        <button 
                            id="people-subtab-alumni"
                            onclick="switchPeopleSubTab('alumni')"
                            class="flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-transparent text-slate-400 hover:text-slate-600 font-medium"
                        >
                            <span>STLab Alumni</span>
                            <span class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-transparent indicator"></span>
                        </button>
                    </div>

                    <!-- Container 1: PI -->
                    <div id="people-container-pi" class="space-y-6">
                        <!-- Director Profile Box (Bento Board Presentation) -->
                        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 sm:p-8 flex flex-col gap-6">
                        
                        <!-- Header Profile Info Card -->
                        <div class="flex flex-col md:flex-row gap-6 items-start md:items-center bg-gradient-to-r from-st-blue/5 to-transparent p-5 rounded-xl border border-slate-200 shadow-xs">
                            
                            <!-- PI Photo Frame with Dual Fallback support -->
                            <div class="h-32 w-32 md:h-36 md:w-36 rounded-2xl bg-[#072138] border-2 border-st-orange/30 overflow-hidden shadow-md shrink-0 relative group flex items-center justify-center">
                                <img 
                                    src="assets/ganti_murthy.jpeg" 
                                    alt="Prof. Ganti Suryanarayana Murthy"
                                    class="h-full w-full object-cover transition-transform duration-300 hover:scale-110"
                                    id="director-photo-php"
                                    onerror="this.style.display='none'; document.getElementById('director-fallback-php').classList.remove('hidden');"
                                    referrerpolicy="no-referrer"
                                />
                                <div id="director-fallback-php" class="hidden absolute inset-0 bg-gradient-to-tr from-[#0d56a6] to-[#052449] flex flex-col justify-center items-center text-white p-3 text-center">
                                    <span class="text-3xl font-extrabold tracking-tight">GSM</span>
                                    <span class="text-[8.5px] font-mono tracking-widest text-[#ffa04d] font-bold mt-1 uppercase bg-black/30 px-1.5 py-0.5 rounded">DIRECTOR</span>
                                </div>
                                <div class="absolute inset-0 ring-1 ring-inset ring-black/10 rounded-2xl pointer-events-none"></div>
                            </div>

                            <!-- PI Identity Details -->
                            <div class="space-y-3 flex-grow">
                                <div class="space-y-1">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-st-green/10 text-st-green border border-st-green/15 inline-block">
                                        Principal Investigator
                                    </span>
                                    <h3 class="text-2xl font-extrabold text-[#0d56a6] tracking-tight">
                                        Prof. Ganti Suryanarayana Murthy
                                    </h3>
                                    <p class="text-sm font-bold text-slate-800 leading-snug">
                                        Chair Professor, MFS-Bio Sciences and Biomedical Engineering
                                    </p>
                                </div>

                                <!-- Sub-Roles list -->
                                <div class="space-y-1.5 text-xs text-slate-600 font-sans">
                                    <div class="flex gap-2 items-start">
                                        <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                        <span class="font-medium text-slate-700 leading-tight">Adani Indology Chair Professor (Agriculture), IIT Indore (April 2025 – Present)</span>
                                    </div>
                                    <div class="flex gap-2 items-start">
                                        <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                        <span class="font-medium text-slate-700 leading-tight">National Coordinator, IKS Division, Ministry of Education, Govt. of India (Oct 2021 – Present)</span>
                                    </div>
                                    <div class="flex gap-2 items-start">
                                        <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                        <span class="font-medium text-slate-700 leading-tight">Professor In-Charge & Founding Head, Centre for Indian Scientific Knowledge Systems (CISKS), IIT Indore</span>
                                    </div>
                                    <div class="flex gap-2 items-start">
                                        <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                        <span class="font-medium text-slate-700 leading-tight">Courtesy Professor, Biological and Ecological Engineering, Oregon State University, USA</span>
                                    </div>
                                    <div class="flex gap-2 items-start">
                                        <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                        <span class="font-medium text-slate-700 leading-tight">Co-Founder & Chairman, Leachate Treatment Systems, Houston, Texas (Aug 2025 – Present)</span>
                                    </div>
                                </div>

                                <!-- Communication tags -->
                                <div class="flex flex-wrap gap-4 pt-1 text-[11px] text-slate-500 font-mono border-t border-slate-200 pb-2">
                                    <span class="flex items-center gap-1.5 hover:text-st-blue transition-colors animate-pulse cursor-pointer">
                                        <i data-lucide="mail" class="h-3.5 w-3.5 text-st-orange shrink-0"></i>
                                        ganti.murthy@iiti.ac.in
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <i data-lucide="map-pin" class="h-3.5 w-3.5 text-st-green shrink-0"></i>
                                        Helium Building (POD 1C), Room 450, Simrol Campus, IIT Indore
                                    </span>
                                </div>

                                <!-- Academic & Professional Profiles -->
                                <div class="pt-2.5 border-t border-slate-200">
                                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest block mb-1.5">
                                        Academic & Professional Portals
                                    </span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <a href="https://www.linkedin.com/in/ganti-murthy-72aa4b4/" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>LinkedIn</span>
                                        </a>
                                        <a href="https://iiti.irins.org/profile/113223" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>IIT Indore Profile</span>
                                        </a>
                                        <a href="https://scholar.google.com/citations?user=RDDboswAAAAJ&hl=en" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>Google Scholar</span>
                                        </a>
                                        <a href="https://iksindia.org/team/" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>IKS Team</span>
                                        </a>
                                        <a href="https://www.researchgate.net/profile/Ganti-Murthy" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>ResearchGate</span>
                                        </a>
                                        <a href="https://www.sciencedirect.com/journal/bioresource-technology/about/editorial-board" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>Bioresource Technology Board</span>
                                        </a>
                                        <a href="https://bee.oregonstate.edu/users/ganti-murthy" target="_blank" rel="noreferrer" class="inline-flex items-center gap-1 px-2.5 py-1 text-[10.5px] font-bold text-st-blue bg-neutral-100 hover:bg-[#0c2340] hover:text-white border border-slate-200 hover:border-transparent rounded-md transition-all cursor-pointer">
                                            <i data-lucide="globe" class="h-3 w-3 shrink-0"></i>
                                            <span>OSU Profile</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bento Details Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            
                            <!-- Grid Col L: About / Bio -->
                            <div class="lg:col-span-7 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3.5 h-full">
                                    <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-slate-100 pb-1.5 flex items-center gap-2">
                                        <i data-lucide="award" class="h-4 w-4 text-st-orange"></i>
                                        About & Philosophy
                                    </h4>
                                    <div class="space-y-3 text-xs text-slate-600 leading-relaxed font-sans">
                                        <p>Prof. Ganti S. Murthy bridges the gap between advanced bioprocess engineering, sustainable systems analysis, and the rich heritage of Indian Knowledge Systems (IKS). With over 15 years of academic leadership at Oregon State University and currently serving as the prestigious Adani Indology Chair Professor (Agriculture) at IIT Indore, his work spans the food-energy-water nexus, techno-economic life cycle assessments, and the modernization of traditional Indian agricultural and mathematical frameworks.</p>
                                        <p>In addition to his academic research, Prof. Murthy is deeply involved in national policy as the National Coordinator for the IKS Division under the Ministry of Education, Govt. of India. He is also an active entrepreneur, having co-founded both GRW Engineering LLC and Leachate Treatment Systems to commercialize sustainable effluent-treatment technologies developed in academic labs.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Grid Col R: Current Leadership & Affiliations -->
                            <div class="lg:col-span-5 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3 h-full flex flex-col justify-between">
                                    <div>
                                        <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-slate-100 pb-1.5 flex items-center gap-2">
                                            <i data-lucide="building" class="h-4 w-4 text-st-green"></i>
                                            Current Leadership
                                        </h4>
                                        <div class="space-y-3 pt-2 text-xs font-sans">
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="font-bold text-slate-900 leading-tight">Adani Indology Chair Professor (Agriculture)</span>
                                                    <span class="text-[10px] text-slate-400 font-mono shrink-0">Apr 2025 – Present</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 font-medium">IIT Indore, Biosciences and Biomedical Engineering</p>
                                                <div class="mt-1 pb-1">
                                                    <p class="text-[9.5px] text-slate-550 bg-amber-50/50 border-l-2 border-st-orange/40 p-2 rounded-r-lg italic leading-normal">
                                                        Spanning agricultural systems engineering and traditional ecology integration with modern biotechnology.
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="font-bold text-slate-900 leading-tight">Professor</span>
                                                    <span class="text-[10px] text-slate-400 font-mono shrink-0">Dec 2019 – Present</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 font-medium">Indian Institute of Technology (IIT) Indore</p>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="font-bold text-slate-900 leading-tight">National Coordinator</span>
                                                    <span class="text-[10px] text-slate-400 font-mono shrink-0">Oct 2021 – Present</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 font-medium">IKS Division, Ministry of Education, Govt. of India at AICTE, New Delhi</p>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="font-bold text-slate-900 leading-tight">Professor In-Charge & Head</span>
                                                    <span class="text-[10px] text-slate-400 font-mono shrink-0 font-none">Nov 2020 – Present</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 font-medium">Center for Indian Scientific Knowledge Systems (CISKS), IIT Indore</p>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="font-bold text-slate-900 leading-tight">Courtesy Professor</span>
                                                    <span class="text-[10px] text-slate-400 font-mono shrink-0">Jan 2022 – Present</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 font-medium">Oregon State University, Corvallis, OR, USA</p>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-start gap-2">
                                                    <span class="font-bold text-slate-900 leading-tight">Co-Founder & Chairman</span>
                                                    <span class="text-[10px] text-slate-400 font-mono shrink-0">Aug 2025 – Present</span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 font-medium">Leachate Treatment Systems, Houston, TX, USA</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2 Grid: Academic Experience (OSU Timeline) -->
                            <div class="lg:col-span-4 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3.5">
                                    <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-slate-100 pb-1.5 flex items-center gap-2">
                                        <i data-lucide="briefcase" class="h-4 w-4 text-st-orange"></i>
                                        Academic Tenure USA
                                    </h4>
                                    
                                    <!-- OSU Academic Timeline -->
                                    <div class="space-y-4 pt-1.5 relative before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                                        <div class="relative pl-6 space-y-1">
                                            <div class="absolute left-[5px] top-1.5 h-2 w-2 rounded-full border border-st-orange bg-white"></div>
                                            <div class="flex justify-between items-baseline gap-2">
                                                <span class="text-xs font-bold text-slate-800 leading-none">Professor (Tenured)</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono shrink-0">Jul 2017 – Feb 2022</span>
                                            </div>
                                            <p class="text-[10.5px] text-slate-500 leading-none">Oregon State University, USA</p>
                                        </div>
                                        <div class="relative pl-6 space-y-1">
                                            <div class="absolute left-[5px] top-1.5 h-2 w-2 rounded-full border border-st-orange bg-white"></div>
                                            <div class="flex justify-between items-baseline gap-2">
                                                <span class="text-xs font-bold text-slate-800 leading-none">Associate Professor (Tenured)</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono shrink-0">Jun 2013 – Jun 2017</span>
                                            </div>
                                            <p class="text-[10.5px] text-slate-500 leading-none">Oregon State University, USA</p>
                                        </div>
                                        <div class="relative pl-6 space-y-1">
                                            <div class="absolute left-[5px] top-1.5 h-2 w-2 rounded-full border border-st-orange bg-white"></div>
                                            <div class="flex justify-between items-baseline gap-2">
                                                <span class="text-xs font-bold text-slate-800 leading-none">Assistant Professor (Tenure Track)</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono shrink-0">Jan 2007 – May 2013</span>
                                            </div>
                                            <p class="text-[10.5px] text-slate-500 leading-none">Oregon State University, USA</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2 Grid: Entrepreneurship & Corporate Career -->
                            <div class="lg:col-span-4 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3.5 h-full">
                                    <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-[#0d56a6]/10 pb-1.5 flex items-center gap-2">
                                        <i data-lucide="sprout" class="h-4 w-4 text-st-green"></i>
                                        Entrepreneurship & Industry
                                    </h4>
                                    <div class="space-y-4 text-xs font-sans">
                                        <div class="space-y-1">
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="font-bold text-slate-900 leading-tight">Co-Founder & Chairman</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono shrink-0">Aug 2025 – Present</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 font-medium">Leachate Treatment Systems, Houston, TX</p>
                                            <p class="text-[10.5px] text-slate-500 italic leading-snug mt-1 border-t border-dashed border-slate-100 pt-1">
                                                Patented wastewater treatment systems developed in STL.
                                            </p>
                                        </div>
                                        <div class="space-y-1 pt-0.5">
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="font-bold text-slate-900 leading-tight">Co-Founder, CEO & Chairman</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono shrink-0">Dec 2019 – Aug 2025</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 font-medium">GRW Engineering LLC, Corvallis, OR</p>
                                            <p class="text-[10.5px] text-slate-500 italic leading-snug mt-1 border-t border-dashed border-slate-100 pt-1">
                                                Commercialized biological filtrate systems for landfill waste effluent.
                                            </p>
                                        </div>
                                        <div class="space-y-1 pt-0.5">
                                            <div class="flex justify-between items-start gap-2">
                                                <span class="font-bold text-slate-900 leading-tight">Software Engineer</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono shrink-0">2003</span>
                                            </div>
                                            <p class="text-[11px] text-slate-600 font-medium">Tata Consultancy Services</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 2 Grid: Education Credentials -->
                            <div class="lg:col-span-4 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3.5 h-full">
                                    <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-slate-100 pb-1.5 flex items-center gap-2">
                                        <i data-lucide="graduation-cap" class="h-4 w-4 text-st-orange"></i>
                                        Educational Lineage
                                    </h4>
                                    <div class="space-y-4 pt-1 text-xs">
                                        <div class="relative pl-6 space-y-1.5 group">
                                            <div class="absolute left-0 top-0.5">
                                                <i data-lucide="graduation-cap" class="h-4 w-4 text-st-green font-bold"></i>
                                            </div>
                                            <div>
                                                <div class="flex justify-between items-baseline gap-2">
                                                    <span class="font-bold text-slate-800 leading-tight">Ph.D. in Agricultural Engineering (Food & Bioprocess)</span>
                                                    <span class="text-[9.5px] text-slate-400 font-mono shrink-0">2003 – 2006</span>
                                                </div>
                                                <p class="text-[10.5px] text-slate-550 leading-snug">University of Illinois at Urbana-Champaign, USA</p>
                                                <p class="text-[9.5px] text-slate-400 italic">Dissertation: Development of a controller for fermentation in dry grind corn process (Advisor: Prof. Vijay Singh)</p>
                                            </div>
                                            <div class="pt-1.5 border-t border-dashed border-slate-100">
                                                <span class="text-[8.5px] font-bold text-st-orange uppercase tracking-wider block mb-1">Academic Courses Taken:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Advanced Bioprocess Engineering</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Fermentation Control & Automation</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Enzyme Technology</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Advanced Biochemical Kinetics</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Applied Mathematical Modeling</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative pl-6 space-y-1.5 group">
                                            <div class="absolute left-0 top-0.5">
                                                <i data-lucide="graduation-cap" class="h-4 w-4 text-st-green font-bold"></i>
                                            </div>
                                            <div>
                                                <div class="flex justify-between items-baseline gap-2">
                                                    <span class="font-bold text-slate-800 leading-tight">M.Tech in Food and Agricultural Engineering (Dairy & Food)</span>
                                                    <span class="text-[9.5px] text-slate-400 font-mono shrink-0">2001 – 2003</span>
                                                </div>
                                                <p class="text-[10.5px] text-slate-550 leading-snug">Indian Institute of Technology (IIT), Kharagpur, India</p>
                                                <p class="text-[9.5px] text-slate-400 italic">Thesis: Modeling and simulation of microwave heating of food materials (Advisor: Prof. Suresh Prasad)</p>
                                            </div>
                                            <div class="pt-1.5 border-t border-dashed border-slate-100">
                                                <span class="text-[8.5px] font-bold text-st-orange uppercase tracking-wider block mb-1">Academic Courses Taken:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Dairy & Food Process Engineering</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Advanced Microwave Food Processing</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Biological System Modeling & Simulation</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Thermodynamics of Food Materials</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Advanced Fluid Mechanics</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="relative pl-6 space-y-1.5 group">
                                            <div class="absolute left-0 top-0.5">
                                                <i data-lucide="graduation-cap" class="h-4 w-4 text-st-green font-bold"></i>
                                            </div>
                                            <div>
                                                <div class="flex justify-between items-baseline gap-2">
                                                    <span class="font-bold text-slate-800 leading-tight">B.Tech in Agricultural Engineering</span>
                                                    <span class="text-[9.5px] text-slate-400 font-mono shrink-0">1997 – 2001</span>
                                                </div>
                                                <p class="text-[10.5px] text-slate-550 leading-snug">North Eastern Hill University, Shillong, India</p>
                                                <p class="text-[9.5px] text-slate-400 italic">Graduated with honors in June 2001</p>
                                            </div>
                                            <div class="pt-1.5 border-t border-dashed border-slate-100">
                                                <span class="text-[8.5px] font-bold text-st-orange uppercase tracking-wider block mb-1">Academic Courses Taken:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Fluid Mechanics & Hydraulics</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Thermodynamics</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Unit Operations in Food Engineering</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Bio-resource Engineering Design</span>
                                                    <span class="text-[9.5px] font-medium bg-slate-50 border border-slate-200/60 rounded px-1.5 py-0.5 text-slate-600 block">Soil & Water Conservation Engineering</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 3 Grid: Key Awards & Recognitions -->
                            <div class="lg:col-span-6 space-y-4">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3.5 h-full">
                                    <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-slate-100 pb-1.5 flex items-center gap-2 font-sans font-medium">
                                        <i data-lucide="award" class="h-4 w-4 text-st-orange"></i>
                                        Academic Awards & Honors
                                    </h4>
                                    <div class="space-y-3 pt-1 text-xs font-sans">
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">Adani Indology Chair Professor (Agriculture)</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono ml-2">(2025)</span>
                                                <p class="text-[10px] text-slate-500">Established to lead pioneering research in traditional ecological agriculture & advanced modern systems.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">ASABE Honorable Mention Paper Award</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono ml-2">(2013)</span>
                                                <p class="text-[10px] text-slate-500">American Society of Agricultural and Biological Engineers, recognized for outstanding publication impact.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">ASABE Outstanding Reviewer Award</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono ml-2">(2009 & 2011)</span>
                                                <p class="text-[10px] text-slate-500">For peer review quality and contribution to academic society development.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">ARF Faculty Fellowship</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono ml-2">(2009)</span>
                                                <p class="text-[10px] text-slate-500">Agricultural Research Foundation, OSU, for biofuel system dynamic modeling.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-orange rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">IIT Kharagpur Silver Medal</span>
                                                <span class="text-[9.5px] text-slate-400 font-mono ml-2">(2003)</span>
                                                <p class="text-[10px] text-slate-500">Awarded for the highest academic performance (1st rank) in Food and Agricultural Engineering at M.Tech level.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Row 3 Grid: Courses Taught & Developed -->
                            <div class="lg:col-span-6 space-y-4 font-sans">
                                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-xs space-y-3.5 h-full">
                                    <h4 class="text-xs font-bold text-[#0d56a6] uppercase tracking-widest border-b border-slate-100 pb-1.5 flex items-center gap-2 font-medium">
                                        <i data-lucide="graduation-cap" class="h-4 w-4 text-st-green font-bold"></i>
                                        Courses Taught & Developed
                                    </h4>
                                    <div class="space-y-3 pt-1 text-xs font-normal">
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-green rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">BSE 624: Bioprocess Engineering & Technology</span>
                                                <p class="text-[10px] text-slate-550">Core postgraduate/doctoral curriculum covering reaction kinetics, upstream processes, and scale-up dynamics at IIT Indore.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-green rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">BEE 472/572: Ecological Engineering Design</span>
                                                <p class="text-[10px] text-slate-550">Signature senior design course at Oregon State University combining ecosystem kinetics, thermodynamics, and physical design principles.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-green rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">BEE 482/582: Ecological Systems Analysis</span>
                                                <p class="text-[10px] text-slate-550">Pioneering course developed at OSU addressing cradle-to-grave life cycle impacts (LCA), techno-economics (TEA), and process carbon accounting.</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 items-start">
                                            <span class="h-1.5 w-1.5 bg-st-green rounded-full mt-1.5 shrink-0"></span>
                                            <div class="flex-1">
                                                <span class="font-bold text-slate-800 leading-tight">Indian Knowledge Systems (IKS) & Scientific Foundations</span>
                                                <p class="text-[10px] text-slate-550">State-level and AICTE reference curriculum mapping ancient Indian agronomy, mathematics, forestation, and water management standards into modern ecological curricula.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Advising & Supervision Dashboard Board -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4 mb-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-slate-100 pb-2.5 gap-2.5">
                            <div>
                                <h4 class="text-xs font-bold text-[#0c2340] uppercase tracking-widest flex items-center gap-2">
                                    <i data-lucide="users" class="h-4.5 w-4.5 text-st-orange"></i>
                                    Mentorship & Collaboration Dashboard
                                </h4>
                                <p class="text-[10px] text-slate-500 font-sans mt-0.5">Statistical summary of scholarly advising, committees, and collaborative networks worldwide.</p>
                            </div>
                            <span class="text-[9px] bg-slate-50 border border-slate-200 font-mono text-slate-500 px-2 py-0.5 rounded font-bold uppercase shrink-0">
                                Global Record
                            </span>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 font-sans text-[11px]">
                            <!-- Block 1: Major Professor -->
                            <div class="bg-slate-50/60 border border-slate-200 p-3.5 rounded-xl space-y-2.5 hover:border-st-orange/20 transition-all">
                                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-1.5">
                                    <i data-lucide="graduation-cap" class="h-4 w-4 text-st-orange shrink-0"></i>
                                    <span class="font-bold text-slate-700">As Major Professor</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Ph.D. Graduates</span>
                                        <span class="font-extrabold text-[#0c2340] bg-[#ffa04d]/20 px-2 py-0.5 rounded-md text-[10px]">14</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">M.S. Graduates</span>
                                        <span class="font-extrabold text-[#0c2340] bg-[#ffa04d]/20 px-2 py-0.5 rounded-md text-[10px]">13</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">B.S. Graduates</span>
                                        <span class="font-extrabold text-[#0c2340] bg-[#ffa04d]/20 px-2 py-0.5 rounded-md text-[10px]">7</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Block 2: Minor & Committee -->
                            <div class="bg-slate-50/60 border border-slate-200 p-3.5 rounded-xl space-y-2.5 hover:border-st-blue/20 transition-all">
                                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-1.5">
                                    <i data-lucide="users" class="h-4 w-4 text-st-green shrink-0"></i>
                                    <span class="font-bold text-slate-700">As Minor / Committee</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Ph.D. Advisory</span>
                                        <span class="font-extrabold text-[#0c2340] bg-slate-100 px-2 py-0.5 rounded-md text-[10px]">3</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">M.S. Advisory</span>
                                        <span class="font-extrabold text-[#0c2340] bg-slate-100 px-2 py-0.5 rounded-md text-[10px]">4</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Grad Council Rep</span>
                                        <span class="font-extrabold text-[#0c2340] bg-slate-100 px-2 py-0.5 rounded-md text-[10px]">8</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Block 3: Scholars & Interns -->
                            <div class="bg-slate-50/60 border border-slate-200 p-3.5 rounded-xl space-y-2.5 hover:border-st-green/20 transition-all">
                                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-1.5">
                                    <i data-lucide="award" class="h-4 w-4 text-st-green shrink-0"></i>
                                    <span class="font-bold text-slate-700">Scholars & Mentoring</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">PostDoc Scholars</span>
                                        <span class="font-extrabold text-st-green bg-st-green/10 px-2 py-0.5 rounded-md text-[10px]">3</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Research Visitors</span>
                                        <span class="font-extrabold text-st-green bg-st-green/10 px-2 py-0.5 rounded-md text-[10px]">3</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100 font-sans">
                                        <span class="text-slate-500">High School Interns</span>
                                        <span class="font-extrabold text-st-green bg-st-green/10 px-2 py-0.5 rounded-md text-[10px]">16</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Block 4: Collaborators -->
                            <div class="bg-slate-50/60 border border-slate-200 p-3.5 rounded-xl space-y-2.5 hover:border-amber-500/20 transition-all">
                                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-1.5">
                                    <i data-lucide="globe" class="h-4 w-4 text-amber-500 shrink-0"></i>
                                    <span class="font-bold text-slate-700">Collaborator Network</span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Oregon State (OSU)</span>
                                        <span class="font-extrabold text-amber-600 bg-amber-500/10 px-2 py-0.5 rounded-md text-[10px]">25</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Other Universities</span>
                                        <span class="font-extrabold text-amber-600 bg-amber-500/10 px-2 py-0.5 rounded-md text-[10px]">17</span>
                                    </div>
                                    <div class="flex justify-between items-center bg-white px-2.5 py-1.5 rounded-lg border border-slate-100">
                                        <span class="text-slate-500">Industry & Agencies</span>
                                        <span class="font-extrabold text-amber-600 bg-amber-500/10 px-2 py-0.5 rounded-md text-[10px]">11</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- Container 2: Active Scholars -->
                    <div id="people-container-scholars" class="hidden space-y-6">
                        <div class="space-y-3.5 pt-3">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-1 flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Active Researchers
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php
                                $activeScholars = [
                                    [
                                        "initials" => "SM", 
                                        "name" => "Sagnik Mitra", 
                                        "desig" => "Ph.D. Researcher (Expected Spring 2027)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Hexadic Tank Modelling",
                                        "email" => "phd2001271007@iiti.ac.in"
                                    ],
                                    [
                                        "initials" => "KS", 
                                        "name" => "Kavita Singh", 
                                        "desig" => "Ph.D. Researcher (Expected Autumn 2026)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Bioprocess Scale-up and Control",
                                        "email" => "phd2001271007@iiti.ac.in"
                                    ],
                                    [
                                        "initials" => "RD", 
                                        "name" => "Raveena Dhore", 
                                        "desig" => "Ph.D. Researcher (Expected Autumn 2026)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Toxicity of Non-stick Cookware Under Indian Cooking Conditions",
                                        "email" => "phd2001271006@iiti.ac.in"
                                    ],
                                    [
                                        "initials" => "MR", 
                                        "name" => "Duddugunta Mohanchaitanya Reddy", 
                                        "desig" => "Ph.D. Researcher (Expected Summer 2027)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Plant–Microbiome–Soil (Kuṇapajala)",
                                        "email" => "mscphd2301171012@iiti.ac.in"
                                    ],
                                    [
                                        "initials" => "BG", 
                                        "name" => "Buddhodev Ghosh", 
                                        "desig" => "Ph.D. Researcher (Expected Summer 2029)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Traditional Rainfall Prediction Models of Bhārat",
                                        "email" => "phd2501192002@iiti.ac.in, buddhodevghosh@gmail.com"
                                    ],
                                    [
                                        "initials" => "KK", 
                                        "name" => "Katta Keerthana", 
                                        "desig" => "Ph.D. Researcher (Expected Summer 2030)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Pest Management Using IKS Approaches",
                                        "email" => "phd2501171011@iiti.ac.in"
                                    ],
                                    [
                                        "initials" => "CS", 
                                        "name" => "Chandrabhan Singh", 
                                        "desig" => "M.Tech. Student (Expected Summer 2027)", 
                                        "inst" => "IIT Indore",
                                        "project" => "Design and Fabrication of a Low-cost Automated FPLC System",
                                        "email" => "mt2502171006@iiti.ac.in"
                                    ],
                                    [
                                        "initials" => "SS", 
                                        "name" => "Shashank Sanjay Shinde", 
                                        "desig" => "M.Tech. Student (Expected Summer 2027)", 
                                        "inst" => "IIT Indore",
                                        "project" => "IR-Based Glucose Sensing and Closed-Loop Insulin Delivery System",
                                        "email" => "mt2502171020@iiti.ac.in"
                                    ]
                                ];
                                foreach ($activeScholars as $sch) {
                                ?>
                                <div class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6 flex flex-col justify-between text-xs hover:shadow-md transition-all space-y-4">
                                    <div>
                                        <div class="flex items-center justify-between gap-2">
                                            <h5 class="font-bold text-blue-700 text-sm"><?php echo htmlspecialchars($sch['name']); ?></h5>
                                        </div>
                                        <p class="text-[10.5px] text-st-orange font-semibold uppercase tracking-wider mt-1"><?php echo htmlspecialchars($sch['desig']); ?></p>
                                        
                                        <div class="mt-3.5 pt-3 border-t border-slate-100 space-y-2.5">
                                            <div class="flex items-start gap-2 text-[11px] text-slate-700 font-sans">
                                                <i data-lucide="flask-conical" class="h-3.5 w-3.5 text-st-orange shrink-0 mt-0.5"></i>
                                                <div>
                                                    <span class="font-bold text-slate-800">Project: </span>
                                                    <span class="text-slate-600 font-medium leading-relaxed"><?php echo htmlspecialchars($sch['project']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-start gap-2 text-[10.5px] text-slate-600 font-sans">
                                                <i data-lucide="mail" class="h-3.5 w-3.5 text-st-blue shrink-0 mt-0.5"></i>
                                                <div class="flex flex-col gap-1">
                                                    <?php
                                                    $emailsList = is_array($sch['email']) ? $sch['email'] : array_map('trim', explode(',', $sch['email']));
                                                    foreach ($emailsList as $em) {
                                                        if (!empty($em)) {
                                                    ?>
                                                        <a href="mailto:<?php echo htmlspecialchars($em); ?>" class="hover:text-st-blue transition-colors font-mono hover:underline break-all">
                                                            <?php echo htmlspecialchars($em); ?>
                                                        </a>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 text-[11px] text-slate-500 font-sans">
                                        <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                                            <i data-lucide="building" class="h-3.5 w-3.5 text-slate-400"></i>
                                            <?php echo htmlspecialchars($sch['inst']); ?>
                                        </span>
                                        <span class="text-[9.5px] bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-0.5 rounded-full font-bold uppercase tracking-wider">Active Scholar</span>
                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Container 3: Alumni -->
                    <div id="people-container-alumni" class="hidden space-y-6">
                        <div class="space-y-3.5 pt-3">
                            <h4 class="text-xs font-bold text-slate-400 tracking-widest border-b border-slate-100 pb-1 flex items-center gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                                STLab Alumni & Last Affiliation
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php
                                $alumniList = [
                                    ["initials" => "AK", "name" => "Abhishek D. Kalbande", "desig" => "M.Sc. Student", "current" => "Symbiotec Zenfold Pvt. Ltd, Ujjain (M.P.)"],
                                    ["initials" => "SP", "name" => "Dr. Sonam Paliya", "desig" => "Post-Doctoral Fellow", "current" => "IIT Indore (Tenure: 2022–2023)"],
                                    ["initials" => "AJ", "name" => "Akshay Udaykumar Jangam", "desig" => "Junior Research Fellow (JRF)", "current" => "IIT Indore (Tenure: 2020–2024)"],
                                    ["initials" => "AA", "name" => "Ashlesha Alone", "desig" => "Junior Research Fellow (JRF)", "current" => "IIT Indore (Tenure: 2021–2022)"],
                                    ["initials" => "SW", "name" => "Steve White", "desig" => "Ph.D. Graduate (2023)", "current" => "CTO of Leachate Treatment Systems, USA"],
                                    ["initials" => "KP", "name" => "Kyle Proctor", "desig" => "Ph.D. Graduate (2022)", "current" => "Oregon State University, USA"],
                                    ["initials" => "HK", "name" => "Dr. Haider J. Khadum", "desig" => "Ph.D. Graduate (2019)", "current" => "Professor and Dean at Babylon University, Iraq"],
                                    ["initials" => "ST", "name" => "Dr. Syed M.H. Tabatabaie", "desig" => "Ph.D. Graduate (2017)", "current" => "Head of North America Operations, Iwatami Corporation, USA"],
                                    ["initials" => "WH", "name" => "Dr. William Hohenschuh", "desig" => "Ph.D. Graduate (2016)", "current" => "Vice President, EMD Serono, Merck Inc., USA"],
                                    ["initials" => "AJ", "name" => "Dr. Ankita Juneja", "desig" => "Ph.D. Graduate (2015)", "current" => "Tenure Track Assistant Professor, SUNY-ESF, NY, USA"],
                                    ["initials" => "DK", "name" => "Dr. Deepak Kumar", "desig" => "Ph.D. Graduate (2014)", "current" => "Tenured Associate Professor, SUNY-ESF, NY, USA"],
                                    ["initials" => "AA", "name" => "Dr. Abraham M. Asmare", "desig" => "Ph.D. Graduate (2014)", "current" => "Professor at Bahir Dar University, Ethiopia"],
                                    ["initials" => "MC", "name" => "Mohanchaitanya", "desig" => "M.S. Graduate (2023)", "current" => "Research Scholar, IIT Indore"],
                                    ["initials" => "HL", "name" => "Harshita Liti", "desig" => "M.S. Graduate (2022)", "current" => "MS Student, IIT Indore"],
                                    ["initials" => "AM", "name" => "Alexander McDaniel", "desig" => "M.S. Graduate (2015)", "current" => "Science Lab Technician at Central Oregon Community College, USA"],
                                    ["initials" => "WH", "name" => "William Hohenschuh", "desig" => "M.S. Graduate (2014)", "current" => "Oregon State University, USA"],
                                    ["initials" => "CG", "name" => "Carolina Garcia-Rios", "desig" => "M.S. Graduate (2013)", "current" => "PSM Biotechnology Alumna, USA"],
                                    ["initials" => "WM", "name" => "Wesley Miller", "desig" => "M.S. Graduate (2011)", "current" => "Oregon State University, USA"],
                                    ["initials" => "KS", "name" => "Kyle Sander", "desig" => "M.S. Graduate (2010)", "current" => "Postdoctoral Scholar, University of California, Berkeley, USA"],
                                    ["initials" => "RA", "name" => "Ragothaman Avanasi", "desig" => "M.S. Graduate (2010)", "current" => "Laboratory Manager, Great Lakes Bioenergy Center, USA"],
                                    ["initials" => "CJ", "name" => "Chintan Toshniwal Joshi", "desig" => "M.S. Graduate (2010)", "current" => "Ph.D. Candidate, Colorado State University, USA"],
                                    ["initials" => "CP", "name" => "Cosmo Prindle", "desig" => "M.S. Graduate (2009)", "current" => "Research Collaborator, St. Louis, MO, USA"],
                                    ["initials" => "CO", "name" => "Crystal Oldfield", "desig" => "B.S. Graduate (2015)", "current" => "Laboratory Analyst, Cascadia Labs, USA"]
                                ];
                                foreach ($alumniList as $alum) {
                                ?>
                                <div class="bg-white border border-slate-150 rounded-xl p-4 flex flex-col justify-between text-xs min-h-[7rem] hover:shadow-md transition-all">
                                    <div>
                                        <h5 class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($alum['name']); ?></h5>
                                        <p class="text-[10px] text-slate-400 mt-1 font-semibold uppercase tracking-wider"><?php echo htmlspecialchars($alum['desig']); ?></p>
                                    </div>
                                    <span class="text-[11px] text-st-blue bg-st-blue/10 border border-st-blue/15 px-2 py-1.5 rounded mt-3 block text-center font-bold">
                                        <?php echo htmlspecialchars($alum['current']); ?>
                                    </span>
                                </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <script>
                        function switchPeopleSubTab(subtabId) {
                            const piBtn = document.getElementById('people-subtab-pi');
                            const scholarsBtn = document.getElementById('people-subtab-scholars');
                            const alumniBtn = document.getElementById('people-subtab-alumni');
                            
                            const piContainer = document.getElementById('people-container-pi');
                            const scholarsContainer = document.getElementById('people-container-scholars');
                            const alumniContainer = document.getElementById('people-container-alumni');
                            
                            // Reset buttons classes
                            [piBtn, scholarsBtn, alumniBtn].forEach(btn => {
                                if (btn) {
                                    btn.className = "flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-transparent text-slate-400 hover:text-slate-600 font-medium";
                                    const ind = btn.querySelector('.indicator');
                                    if (ind) ind.className = "absolute bottom-0 left-0 right-0 h-[2.5px] bg-transparent indicator";
                                }
                            });
                            
                            // Hide containers
                            [piContainer, scholarsContainer, alumniContainer].forEach(c => {
                                if (c) c.classList.add('hidden');
                            });
                            
                            // Set active button and container
                            const activeBtn = document.getElementById('people-subtab-' + subtabId);
                            const activeContainer = document.getElementById('people-container-' + subtabId);
                            
                            if (activeBtn && activeContainer) {
                                activeBtn.className = "flex items-center gap-2 px-5 py-3 text-xs font-bold uppercase tracking-wider border-b-2 transition-all relative cursor-pointer border-st-orange text-[#0c2340] font-extrabold";
                                const ind = activeBtn.querySelector('.indicator');
                                if (ind) ind.className = "absolute bottom-0 left-0 right-0 h-[2.5px] bg-st-orange indicator";
                                activeContainer.classList.remove('hidden');
                            }
                        }
                    </script>

                </div>

                <!-- PUBLICATIONS PANE -->
                <div id="pane-publications" class="tab-pane hidden space-y-6 animate-tabFadeIn">
                    <?php
                    $dissertations = [
                        [
                            "author" => "Deepak Kumar",
                            "year" => "2014",
                            "title" => "Biochemical conversion of lignocellulosic biomass to ethanol: experimental, enzymatic hydrolysis modeling, techno-economic and life cycle assessment studies",
                            "institution" => "Oregon State University"
                        ],
                        [
                            "author" => "Abraham Asmare",
                            "year" => "2014",
                            "title" => "Investigation the potential of the microalgae consortium for algal biomass productivity, carbon sequestration and nutrient recovery from dairy manure",
                            "institution" => "Addis Ababa University, Ethiopia"
                        ],
                        [
                            "author" => "Ankita Juneja",
                            "year" => "2015",
                            "title" => "Model predictive control for optimum algal growth",
                            "institution" => "Oregon State University"
                        ],
                        [
                            "author" => "William Hohenschuh",
                            "year" => "2016",
                            "title" => "The path forward: Using high throughput data and dynamic flux balance modeling to identify bottlenecks in the carbon metabolism of industrial microbes and suggest solutions to improve product yield",
                            "institution" => "Oregon State University"
                        ],
                        [
                            "author" => "S.M.H. Tabatabaie",
                            "year" => "2017",
                            "title" => "Integrated spatio-temporal sustainability analysis of biofuels using biogeochemistry, economic and life cycle analysis",
                            "institution" => "Oregon State University"
                        ],
                        [
                            "author" => "Haider J. Kadhum",
                            "year" => "2019",
                            "title" => "Novel system design and operational strategies for the production of biofuels and bioproducts",
                            "institution" => "Oregon State University"
                        ],
                        [
                            "author" => "Steve White",
                            "year" => "2023",
                            "title" => "A Land Application Treatment Strategy for Landfill Leachate and Water Reclamation",
                            "institution" => "Oregon State University"
                        ],
                        [
                            "author" => "Kyle Proctor",
                            "year" => "2022",
                            "title" => "Harvesting Sunshine: A Modular Integrated Framework for Modelling the Agrivoltaic System",
                            "institution" => "Oregon State University"
                        ]
                    ];

                    $patents = [
                        [
                            "type" => "Patent",
                            "title" => "A dynamic optimal controller for control of fermentation processes",
                            "office" => "Office of technology management, University of Illinois at Urbana-Champaign",
                            "number" => "US Patent No: 7,862,992",
                            "date" => "Issued 4th January, 2011",
                            "inventors" => "Ganti S. Murthy and Vijay Singh",
                            "details" => "This technology has been successfully tested in two commercial dry-grind corn plants, licensed and installed in several corn dry-grind ethanol plants in the US."
                        ],
                        [
                            "type" => "Provisional Patent",
                            "title" => "A multi-scale control framework for sustainable management of engineered algae production systems",
                            "office" => "Office for Commercialization and Corporate Development, Oregon State University",
                            "number" => "US Provisional Patent No: 62/190,642",
                            "date" => "Filed 9th July, 2015",
                            "inventors" => "Ganti S. Murthy and Ankita Juneja"
                        ],
                        [
                            "type" => "Provisional Patent",
                            "title" => "Wastewater treatment system and method",
                            "office" => "Office for Commercialization and Corporate Development, Oregon State University",
                            "number" => "US Provisional Patent No: 62/738,806",
                            "date" => "Filed 28th September, 2018",
                            "inventors" => "Ganti S. Murthy and Steven White"
                        ]
                    ];

                    $other_publications = [
                        [
                            "year" => 2026, 
                            "citation" => "Mitra, S., Murthy, Ganti S. (2026). Experimental Comparison of Five MPC Variants for Hexadic Tank System Control: A Multi-Criteria Hardware Evaluation.",
                            "doi" => "10.1016/j.jprocont.2026.01.002",
                            "link" => "https://doi.org/10.1016/j.jprocont.2026.01.002"
                        ],
                        [
                            "year" => 2016, 
                            "citation" => "Williams, J. D., Robertson, D. S., Long, D. S., Wuest, S. B., Kumar, D., Juneja, A., ... (2016). Ethanol production potential from conservation buffers in the inland Pacific Northwest. Journal of Renewable and Sustainable Energy 8 (5).",
                            "doi" => "10.1063/1.4965809",
                            "link" => "https://doi.org/10.1063/1.4965809"
                        ],
                        [
                            "year" => 2025, 
                            "citation" => "Mitra, S., Paliya, S., Mandpe, A., Murthy, Ganti S. (2025). Maximizing resource valorization through bioremediation process intensification: A journey from bench to pilot scale. Microbial Biotechnology: Integrated Microbial Engineering for B3–Bioenergy.",
                            "doi" => "10.1111/1751-7915.14321",
                            "link" => "https://doi.org/10.1111/1751-7915.14321"
                        ],
                        [
                            "year" => 2024, 
                            "citation" => "Bhat, A. H., Dayal, D., Pandey, A., Murthy, G. S. (2024). Performance evaluation of canal irrigation system at the tertiary level of Upper Ganga Canal using remote sensing. Tropical Ecology 65 (2), 289-306.",
                            "doi" => "10.1007/s42965-023-00320-z",
                            "link" => "https://doi.org/10.1007/s42965-023-00320-z"
                        ],
                        [
                            "year" => 2022, 
                            "citation" => "Mitra, S., Murthy, Ganti S. (2022). Bioreactor control systems in the biopharmaceutical industry: a critical perspective. Systems Microbiology and Biomanufacturing 2 (1), 91-112.",
                            "doi" => "10.1007/s43393-021-00045-w",
                            "link" => "https://doi.org/10.1007/s43393-021-00045-w"
                        ],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Techno-economic assessment. Biomass, Biofuels, Biochemicals, 17-32."],
                        ["year" => 2022, "citation" => "Murthy, G. S., Pandey, A. (2022). Systems analysis and its relevance for the sustainability transitions. Biomass, Biofuels, Biochemicals, 1-16."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Environmental impacts. Biomass, Biofuels, Biochemicals, 33-52."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Resource assessment. Biomass, Biofuels, Biochemicals, 75-98."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Biofuels and bioproducts in India. Biomass, Biofuels, Biochemicals, 209-230."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Policy, governance, and social aspects. Biomass, Biofuels, Biochemicals, 99-112."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). A systems analysis of first-and second-generation ethanol in the United States. Biomass, Biofuels, Biochemicals, 147-174."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Resilience thinking. Biomass, Biofuels, Biochemicals, 113-126."],
                        ["year" => 2022, "citation" => "Murthy, G. S. (2022). Environmental risk assessment. Biomass, Biofuels, Biochemicals, 53-74."],
                        ["year" => 2022, "citation" => "Proctor, K., Murthy, G. S. (2022). A systems analysis of solar and wind energy in the United States. Biomass, Biofuels, Biochemicals, 195-208."],
                        ["year" => 2022, "citation" => "Kadhum, H. J., Murthy, G. S. (2022). Novel system design for high solid lignocellulosic biomass conversion. Bioresource Technology 350, 126897."],
                        ["year" => 2021, "citation" => "Dhore, R., Murthy, G. S. (2021). Per/polyfluoroalkyl substances production, applications and environmental impacts. Bioresource Technology 341, 125808."],
                        ["year" => 2021, "citation" => "Al-Agele, H. A., Proctor, K., Murthy, G., Higgins, C. (2021). A Case Study of Tomato (Solanum lycopersicon var. Legend) Production and Water Productivity in Agrivoltaic Systems. Sustainability 13 (5), 2850."],
                        ["year" => 2021, "citation" => "Proctor, K., Tabatabaie, S. M. H., Murthy, G. S. (2021). Gateway to the perspectives of the Food-Energy-Water nexus. Science of The Total Environment 764, 142852."],
                        ["year" => 2021, "citation" => "Tabatabaie, S. M. H., Murthy, G. S. (2021). Development of an input-output model for food-energy-water nexus in the pacific northwest, USA. Resources, Conservation and Recycling 168, 105267."],
                        ["year" => 2021, "citation" => "Tanzil, A. H., Zhang, X., Wolcott, M., Brandt, K., Stöckle, C., Murthy, G., ... (2021). Evaluation of dry corn ethanol bio-refinery concepts for the production of sustainable aviation fuel. Biomass and Bioenergy 146, 105937."],
                        ["year" => 2021, "citation" => "Mahapatra, D. M., Murthy, G. S. (2021). Long term evaluation of a pilot scale multimodal algal bioprocess for treatment of municipal wastewater. Journal of Cleaner Production 311, 127690."],
                        ["year" => 2022, "citation" => "Murthy, G. S., Gnansounou, E., Khanal, S. K., Pandey, A. (2022). Biomass, biofuels, biochemicals: green-economy: systems analysis for sustainability. Elsevier."],
                        ["year" => 2026, "citation" => "Indian Knowledge Systems, Indian contributions to Sciences, Volume-1", "link" => "https://bharatiya-jnana-sarita.info/published_books/68e781d3ed96123eb8e43269"],
                        ["year" => 2021, "citation" => "Hohenschuh, W., Hector, R. E., Chaplen, F., Murthy, G. S. (2021). Using high-throughput data and dynamic flux balance modeling techniques to identify points of constraint in xylose utilization in Saccharomyces cerevisiae. Systems Microbiology and Biomanufacturing 1 (1), 58-75."],
                        ["year" => 2021, "citation" => "Hohenschuh, W., Hector, R. E., Mertens, J. A., Murthy, G. S. (2021). Development and characterization of Saccharomyces cerevisiae strains genetically modified to over-express the pentose phosphate pathway. Systems Microbiology and Biomanufacturing 1 (1), 42-57."],

                        ["year" => 2020, "citation" => "Proctor, K. W., Murthy, G. S., Higgins, C. W. (2020). Agrivoltaics align with green new deal goals while supporting investment in the US’ rural economy. Sustainability 13 (1), 137."],
                        ["year" => 2020, "citation" => "Cheng, M. H., Kadhum, H. J., Murthy, G. S., Dien, B. S., Singh, V. (2020). High solids loading biorefinery for the production of cellulosic sugars from bioenergy sorghum. Bioresource Technology 318, 124051."],
                        ["year" => 2020, "citation" => "Murthy, G. S. (2020). An Automatic Disinfection System for Passenger Luggage at Airports and Train/Bus Stations. Transactions of the Indian National Academy of Engineering 5, 295-298."],
                        ["year" => 2020, "citation" => "Gnansounou, E., Ganti, M. S., Singh, A., Gabrielle, B. (2020). Systems Analysis and Life-Cycle Assessment for energy and environmental sustainability. Bioresource Technology 317, 123988."],

                        ["year" => 2019, "citation" => "Rajendran, K., Murthy, G. S. (2019). Techno-economic and life cycle assessments of anaerobic digestion–A review. Biocatalysis and Agricultural Biotechnology 20, 101207."],
                        ["year" => 2019, "citation" => "Kadhum, H. J., Mahapatra, D. M., Murthy, G. S. (2019). A comparative account of glucose yields and bioethanol production from separate and simultaneous saccharification and fermentation processes at high solids loading. Bioresource Technology 283, 67-75."],
                        ["year" => 2019, "citation" => "Kadhum, H. J., Mahapatra, D. M., Murthy, G. S. (2019). A novel method for real-time estimation of insoluble solids and glucose concentrations during enzymatic hydrolysis of biomass. Bioresource Technology 275, 328-337."],
                        ["year" => 2019, "citation" => "Murthy, G. S. (2019). Systems analysis frameworks for biorefineries. Biofuels: alternative feedstocks and conversion processes...."],
                        ["year" => 2019, "citation" => "Srivastava, A., Chahar, V., Sharma, V., Swain, K. K., Hoyler, F., Murthy, G. S., ... (2019). Study of Toxic Elements in River Water and Wetland Using Water Hyacinth (Eichhornia crassipes) as Pollution Monitor. Global Challenges 3 (6), 1800087."],
                        ["year" => 2019, "citation" => "Agrawal, A., Vishwakarma, R. K., Tripathi, V. R., Kothari, A. K., Prasad, B., Kumar, J., ... (2019). Improvement in casting practice by controlling the drainage rate and hearth liquid level to develop an efficient casthouse management practice in blast furnace. Ironmaking & Steelmaking 46 (4), 373-382."],
                        ["year" => 2019, "citation" => "Mahapatra, D. M., Mahapatra, R., Singh, L., Kadhum, H. J., Murthy, G. S., ... (2019). Phosphorus capture, immobilization and channeling through algae for a sustainable agriculture. Soil Fertility Management for Sustainable Development, 1-11."],
                        ["year" => 2019, "citation" => "Singh, B. K., Ramdasu, B., Patnaik, D., Raj Kumar, V., M GSR, ... (2019). Heat Loss Control in Stave Cooled Blast Furnace by Optimizing Gas Flow Pattern through Burden Distribution. Sustainable Industrial Processing Summit 8, 73-82."],

                        ["year" => 2019, "citation" => "Mahapatra, D. M., Joshi, N. V., Murthy, G. S., Ramachandra, T. V. (2019). Natural Algal Photobioreactors for Sustainable Wastewater Treatment. Handbook of Algal Technologies and Phytochemicals, 23-36."],
                        ["year" => 2018, "citation" => "Mahapatra, D. M., Chanakya, H. N., Joshi, N. V., Ramachandra, T. V., Murthy, G. S. (2018). Algae-based biofertilizers: a biorefinery approach. Microorganisms for Green Revolution: Volume 2."],
                        ["year" => 2018, "citation" => "Tabatabaie, S. M. H., Tahami, H., Murthy, G. S. (2018). A regional life cycle assessment and economic analysis of camelina biodiesel production in the Pacific Northwestern US. Journal of Cleaner Production 172, 2389-2400."],
                        ["year" => 2018, "citation" => "Kadhum, H. J., Rajendran, K., Murthy, G. S. (2018). Optimization of surfactant addition in cellulosic ethanol process using integrated techno-economic and life cycle assessment for bioprocess design. ACS Sustainable Chemistry & Engineering 6 (11), 13687-13695."],
                        ["year" => 2018, "citation" => "Tabatabaie, S. M. H., Bolte, J. P., Murthy, G. S. (2018). A regional scale modeling framework combining biogeochemical model with life cycle and economic analysis for integrated assessment of cropping systems. Science of the Total Environment 625, 428-439."],
                        ["year" => 2018, "citation" => "Juneja, A., Murthy, G. S. (2018). Model predictive control coupled with economic and environmental constraints for optimum algal production. Bioresource Technology 250, 556-563."],
                        ["year" => 2018, "citation" => "Mellbye, B. L., Giguere, A. T., Murthy, G. S., Bottomley, P. J., Sayavedra-Soto, L. A., ... (2018). Genome-Scale, Constraint-Based Modeling of Nitrogen Oxide Fluxes during Coculture of Nitrosomonas europaea and Nitrobacter winogradskyi. Msystems 3 (3)."],

                        ["year" => 2017, "citation" => "Mirkouei, A., Haapala, K. R., Sessions, J., Murthy, G. S. (2017). A review and future directions in techno-economic modeling and optimization of upstream forest biomass to bio-oil supply chains. Renewable and Sustainable Energy Reviews 67, 15-35."],
                        ["year" => 2017, "citation" => "Mirkouei, A., Haapala, K. R., Sessions, J., Murthy, G. S. (2017). A mixed biomass-based energy supply chain for enhancing economic and environmental sustainability benefits: A multi-criteria decision making framework. Applied Energy 206, 1088-1101."],
                        ["year" => 2017, "citation" => "Juneja, A., Murthy, G. S. (2017). Evaluating the potential of renewable diesel production from algae cultured on wastewater: techno-economic analysis and life cycle assessment. Aims Energy 5 (2), 239."],
                        ["year" => 2017, "citation" => "Kadhum, H. J., Rajendran, K., Murthy, G. S. (2017). Effect of solids loading on ethanol production: Experimental, economic and environmental analysis. Bioresource Technology 244, 108-116."],
                        ["year" => 2017, "citation" => "Rajendran, K., Murthy, G. S. (2017). How does technology pathway choice influence economic viability and environmental impacts of lignocellulosic biorefineries? Biotechnology for Biofuels 10 (1), 268."],
                        ["year" => 2017, "citation" => "Tabatabaie, S. M. H., Murthy, G. S. (2017). Effect of geographical location and stochastic weather variation on life cycle assessment of biodiesel production from camelina in the northwestern USA. The International Journal of Life Cycle Assessment 22 (6), 867-882."],
                        ["year" => 2017, "citation" => "Kumar, D., Murthy, G. S. (2017). Development and validation of a stochastic molecular model of cellulose hydrolysis by action of multiple cellulase enzymes. Bioresources and Bioprocessing 4 (1), 54."],
                        ["year" => 2016, "citation" => "Tabatabaie, S. M. H., Murthy, G. S. (2016). Cradle to farm gate life cycle assessment of strawberry production in the United States. Journal of Cleaner Production 127, 548-554."],
                        ["year" => 2016, "citation" => "Mirkouei, A., Mirzaie, P., Haapala, K. R., Sessions, J., Murthy, G. S. (2016). Reducing the cost and environmental impact of integrated fixed and mobile bio-oil refinery supply chains. Journal of Cleaner Production 113, 495-507."],
                        ["year" => 2016, "citation" => "Juneja, A., Chaplen, F. W. R., Murthy, G. S. (2016). Genome scale metabolic reconstruction of Chlorella variabilis for exploring its metabolic potential for biofuels. Bioresource Technology 213, 103-110."],
                        ["year" => 2016, "citation" => "Kumar, D., Murthy, G. S. (2016). Enzymatic hydrolysis of cellulose for ethanol production: fundamentals, optimal enzyme ratio, and hydrolysis modeling. New and Future Developments in Microbial Biotechnology and Bioengineering, 65-78."],
                        ["year" => 2016, "citation" => "Mirkouei, A., Haapala, K. R., Sessions, J., Murthy, G. S. (2016). Reducing greenhouse gas emissions for sustainable bio-oil production using a mixed supply chain. International Design Engineering Technical Conferences."],
                        ["year" => 2016, "citation" => "Ault, K., Viswanath, V., Jayawickrama, J., Ma, C., Eaton, J., Meilan, R., ... (2016). Improved growth and weed control of glyphosate-tolerant poplars. New Forests 47 (5), 653-667."],
                        ["year" => 2016, "citation" => "Arbuckle, P., Kahn, E., Loneman, A., McCarthy, S., Tabatabaie, S. M. H., ... (2016). Unit process data collection for specialty crop production. Proceedings of the 9th International Conference on Life Cycle Assessment."],
                        ["year" => 2015, "citation" => "Hohenschuh, W., Hector, R., Murthy, G. S. (2015). A dynamic flux balance model and bottleneck identification of glucose, xylose, xylulose co-fermentation in Saccharomyces cerevisiae. Bioresource Technology 188, 153-160."],
                        ["year" => 2014, "citation" => "Kumar, D., Prasad, S., Murthy, G. S. (2014). Optimization of microwave-assisted hot air drying conditions of okra using response surface methodology. Journal of Food Science and Technology 51 (2), 221-232."],
                        ["year" => 2014, "citation" => "Mochizuki, J., Yanagida, J. F., Kumar, D., Takara, D., Murthy, G. S. (2014). Life cycle assessment of ethanol production from tropical banagrass (Pennisetum purpureum) using green and dry processing technologies in Hawaii. Journal of Renewable and Sustainable Energy 6 (4)."],
                        ["year" => 2014, "citation" => "Merculieff, Z., Ramnath, S., Sankoli, S. M., Venkataramegowda, S., Murthy, G. S., ... (2014). Phytochemical, antioxidant and antibacterial potential of Elaeagnus kologa (Schlecht.) leaf. Asian Pacific Journal of Tropical Medicine 7, S599-S602."],
                        ["year" => 2014, "citation" => "Asmare, A. M., Demessie, B. A., Murthy, G. S. (2014). Investigation of microalgae Co-cultures for nutrient recovery and algal BiomassProduction from dairy manure. Applied Engineering in Agriculture 30 (2), 335-342."],
                        ["year" => 2014, "citation" => "Hohenschuh, W., Kumar, D., Murthy, G. S. (2014). Economic and cradle-to-gate life cycle assessment of poly-3-hydroxybutyrate production from plastic producing, genetically modified hybrid poplar leaves. Journal of Renewable and Sustainable Energy 6 (6)."],
                        ["year" => 2014, "citation" => "Hashimoto, A., Arnold, J., Ayars, J., Crow, S., Eggeman, T., Jakeway, L., ... (2014). High-yield tropical biomass for advanced biofuels. Proceedings from Sun Grant National Conference."],
                        ["year" => 2013, "citation" => "Kumar, D., Murthy, G. S. (2013). Stochastic molecular model of enzymatic hydrolysis of cellulose for ethanol production. Biotechnology for Biofuels 6 (1), 63."],
                        ["year" => 2013, "citation" => "Juneja, A., Kumar, D., Murthy, G. S. (2013). Economic feasibility and environmental life cycle assessment of ethanol production from lignocellulosic feedstock in Pacific Northwest US. Journal of Renewable and Sustainable Energy 5 (2)."],
                        ["year" => 2013, "citation" => "Bozorgirad, M. A., Zhang, H., Haapala, K. R., Murthy, G. S. (2013). Environmental impact and cost assessment of incineration and ethanol production as municipal solid waste management strategies. The International Journal of Life Cycle Assessment 18 (8), 1502-1512."],
                        ["year" => 2013, "citation" => "Asmare, A. M., Demessie, B. A., Murthy, G. S. (2013). Theoretical estimation the potential of algal biomass for biofuel production and carbon sequestration in Ethiopia. International Journal of Renewable Energy Research 3 (3), 560-570."],
                        ["year" => 2013, "citation" => "Avanasi, R. N., Murthy, G. S., Chaplen, F. W. R., Beatty, C. (2013). Fermentation of Glucose/Xylose/Xylulose in the Presence of Furfural by Yeast for Ethanol Production. Biological Engineering Transactions 6 (3), 157-172."],
                        ["year" => 2013, "citation" => "Ceballos, R., Sankoli, S. M., Ramnath, S., Venkataramegowda, S., Murthy, G. S., ... (2013). Evaluation of phytochemicals and in vitro antioxidant studies of Toddalia asiatica leaf. Medicinal Plants-International Journal of Phytomedicines and Related Industries."],
                        ["year" => 2012, "citation" => "Kumar, D., Murthy, G. S. (2012). Life cycle assessment of energy and GHG emissions during ethanol production from grass straws using various pretreatment processes. The International Journal of Life Cycle Assessment 17 (4), 388-401."],
                        ["year" => 2012, "citation" => "Murthy, G. S., Johnston, D. B., Rausch, K. D., Tumbleson, M. E., Singh, V. (2012). A simultaneous saccharification and fermentation model for dynamic growth environments. Bioprocess and Biosystems Engineering 35 (4), 519-534."],
                        ["year" => 2012, "citation" => "Kumar, D., Juneja, A., Hohenschuh, W., Williams, J. D., Murthy, G. S. (2012). Chemical composition and bioethanol potential of different plant species found in Pacific Northwest conservation buffers. Journal of Renewable and Sustainable Energy 4 (6)."],
                        ["year" => 2012, "citation" => "Murthy, G. S., Johnston, D. B., Rausch, K. D., Tumbleson, M. E., Singh, V. (2012). Design and evaluation of an optimal controller for simultaneous saccharification and fermentation process. Applied Biochemistry and Biotechnology 166 (1), 87-111."],
                        ["year" => 2012, "citation" => "Dalton, D. A., Murthy, G., Strauss, S. H. (2012). Production of traditional and novel biopolymers in transgenic woody plants. Phytochemicals, Plant Growth, and the Environment, 59-78."],
                        ["year" => 2012, "citation" => "Hohenschuh, W., Ma, C., Dalton, D., Murthy, G. S. (2012). Optimization of a Poly-3-hydroxybutyrate Quantification Method for Rapid Detection in Plant Based Systems. Journal of Bioprocess Engineering and Biorefinery 1 (1), 120-126."],
                        ["year" => 2011, "citation" => "Kumar, D., Murthy, G. S. (2011). Impact of pretreatment and downstream processing technologies on economics and energy in cellulosic ethanol production. Biotechnology for Biofuels 4 (1), 27."],
                        ["year" => 2011, "citation" => "Murthy, G. S., Johnston, D. B., Rausch, K. D., Tumbleson, M. E., Singh, V. (2011). Starch hydrolysis modeling: application to fuel ethanol production. Bioprocess and Biosystems Engineering 34 (7), 879-890."],
                        ["year" => 2011, "citation" => "Murthy, G. S. (2011). Overview and assessment of algal biofuels production technologies. Biofuels, 415-437."],
                        ["year" => 2011, "citation" => "Kumar, D., Murthy, G. S. (2011). Pretreatments and enzymatic hydrolysis of grass straws for ethanol production in the Pacific Northwest US. Biological Engineering Transactions 3 (2), 97-110."],
                        ["year" => 2011, "citation" => "Juneja, A., Kumar, D., Williams, J. D., Wysocki, D. J., Murthy, G. S. (2011). Potential for ethanol production from conservation reserve program lands in Oregon. Journal of Renewable and Sustainable Energy 3 (6)."],
                        ["year" => 2011, "citation" => "Murthy, G. S., Rausch, K. D., Johnston, D. B., Tumbleson, M. E., Singh, V. (2011). Industrial evaluation of a dynamic controller for simultaneous saccharification and fermentation process. Industrial Biotechnology 7 (4), 298-307."],
                        ["year" => 2011, "citation" => "Murthy, G. S., Singh, V. (2011). Dynamic fermentation controller. US Patent 7,862,992."],
                        ["year" => 2010, "citation" => "Sander, K., Murthy, G. S. (2010). Life cycle analysis of algae biodiesel. The International Journal of Life Cycle Assessment 15 (7), 704-714."],
                        ["year" => 2009, "citation" => "Sander, K. B., Murthy, G. S. (2009). Enzymatic degradation of microalgal cell walls. 2009 Reno, Nevada, June 21-June 24, 2009."],
                        ["year" => 2009, "citation" => "Murthy, G. S., Sall, E. D., Metz, S. G., Foster, G., Singh, V. (2009). Evaluation of a dry corn fractionation process for ethanol production with different hybrids. Industrial Crops and Products 29 (1), 67-72."],
                        ["year" => 2009, "citation" => "Miller, W. C., Murthy, G. S. (2009). Evaluation of seven feedstocks for cellulosic ethanol production in the northwestern United States of America. 2009 Reno, Nevada, June 21-June 24, 2009."],
                        ["year" => 2007, "citation" => "Amezcua, C. M., Parsons, C. M., Singh, V., Srinivasan, R., Murthy, G. S. (2007). Nutritional characteristics of corn distillers dried grains with solubles as affected by the amounts of grains versus solubles and different processing techniques. Poultry Science 86 (12), 2624-2630."],
                        ["year" => 2006, "citation" => "Murthy, G. S., Singh, V., Johnston, D. B., Rausch, K. D., Tumbleson, M. E. (2006). Evaluation and strategies to improve fermentation characteristics of modified dry‐grind corn processes. Cereal Chemistry 83 (5), 455-459."],
                        ["year" => 2006, "citation" => "Murthy, G. S., Singh, V., Johnston, D. B., Rausch, K. D., Tumbleson, M. E. (2006). Improvement in fermentation characteristics of degermed ground corn by lipid supplementation. Journal of Industrial Microbiology and Biotechnology 33 (8), 655-660."],
                        ["year" => 2005, "citation" => "Murthy, G. S., Townsend, D. E., Meerdink, G. L., Bargren, G. L., Tumbleson, M. E., ... (2005). Effect of aflatoxin B1 on dry‐grind ethanol process. Cereal Chemistry 82 (3), 302-304."]
                    ];

                    $journal_papers = [];
                    $books_and_chapters = [];

                    foreach ($other_publications as $pub) {
                        $citation = strtolower($pub['citation']);
                        if (strpos($citation, 'patent') !== false) {
                            continue; // Skip patents/repetition
                        }
                        
                        $is_book = (
                            strpos($citation, 'biomass, biofuels, biochemicals') !== false ||
                            strpos($citation, 'indian knowledge systems') !== false ||
                            strpos($citation, 'elsevier') !== false ||
                            strpos($citation, 'handbook') !== false ||
                            strpos($citation, 'soil fertility management') !== false ||
                            strpos($citation, 'microorganisms for green revolution') !== false ||
                            strpos($citation, 'developments in microbial biotechnology') !== false ||
                            strpos($citation, 'phytochemicals, plant growth') !== false ||
                            strpos($citation, 'biofuels, 415-437') !== false ||
                            strpos($citation, 'systems analysis frameworks for biorefineries') !== false ||
                            strpos($citation, 'publishing') !== false ||
                            strpos($citation, 'press') !== false
                        );

                        if ($is_book) {
                            $books_and_chapters[] = $pub;
                        } else {
                            $journal_papers[] = $pub;
                        }
                    }
                    ?>
                    <div class="space-y-1.5">
                        <h3 class="text-lg font-bold text-slate-800 uppercase">Publications & Patents</h3>
                        <p class="text-xs text-slate-500 font-sans">Comprehensive records of stlab innovations, graduate dissertations, and peer-reviewed papers.</p>
                    </div>

                    <!-- Modern Search Bar and Button -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 flex flex-col sm:flex-row gap-3 items-center justify-between shadow-2xs">
                        <div class="w-full sm:w-auto flex-1 relative">
                            <input 
                                type="text" 
                                id="publication-search-input" 
                                onkeyup="filterPublications()"
                                placeholder="Search publications by title, author, journal or year..." 
                                class="w-full pl-9 pr-4 py-2 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-st-blue focus:border-st-blue bg-white font-sans text-slate-750"
                            />
                            <i data-lucide="search" class="absolute left-3 top-2.5 h-3.5 w-3.5 text-slate-400"></i>
                        </div>
                        <button 
                            onclick="filterPublications()" 
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4.5 py-2 text-xs font-bold bg-[#0c2340] hover:bg-st-orange hover:text-[#0c2340] text-white rounded-lg transition-all shadow-sm cursor-pointer whitespace-nowrap"
                        >
                            <i data-lucide="filter" class="h-3.5 w-3.5"></i>
                            Search / Filter
                        </button>
                    </div>

                    <!-- Category Tab Controls in static page (anchored jumps or simple visual separators) -->
                    <div class="flex flex-wrap gap-2 py-2 border-b border-slate-100">
                        <a href="#sec-dissertations" class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-250 text-[#0d56a6] text-xs font-bold font-sans transition-all duration-200">
                            🎓 Dissertations (<?= count($dissertations) ?>)
                        </a>
                        <a href="#sec-patents" class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-250 text-[#0d56a6] text-xs font-bold font-sans transition-all duration-200">
                            💡 Patents (<?= count($patents) ?>)
                        </a>
                        <a href="#sec-journals" class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-250 text-[#0d56a6] text-xs font-bold font-sans transition-all duration-200">
                            📄 Journal Papers (<?= count($journal_papers) ?>)
                        </a>
                        <a href="#sec-books" class="px-3 py-1.5 rounded bg-slate-100 hover:bg-slate-250 text-[#0d56a6] text-xs font-bold font-sans transition-all duration-200">
                            📚 Books & Book Chapters (<?= count($books_and_chapters) ?>)
                        </a>
                    </div>

                    <!-- Featured High Cited & Notable Publications Banner -->
                    <div class="space-y-4 bg-gradient-to-r from-st-orange/5 via-st-blue/5 to-st-orange/5 p-5 rounded-2xl border border-st-orange/15 shadow-sm mt-4">
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 bg-st-orange/15 text-st-orange rounded-lg">
                                <i data-lucide="award" class="h-4 w-4 animate-bounce"></i>
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-slate-800">Notable & Highly Cited Research Highlights</h4>
                                <p class="text-[10px] text-slate-500 font-sans">Seminal papers and commercialized inventions with significant global impact.</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Item 1 -->
                            <div class="bg-white border border-slate-200/80 hover:border-st-orange/30 rounded-xl p-4 transition-all hover:shadow-md card-3d-bevel">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="bg-amber-100 text-amber-805 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase font-mono tracking-widest">
                                        ★ Highly Cited (500+ Cites)
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">2010</span>
                                </div>
                                <p class="text-[12px] text-slate-700 font-medium leading-relaxed mt-2">
                                    Sander, K., Murthy, G. S. (2010). Life cycle analysis of algae biodiesel. <strong>The International Journal of Life Cycle Assessment</strong> 15 (7), 704-714.
                                </p>
                                <div class="mt-2 text-[10px] text-[#0d56a6] bg-[#0d56a6]/5 px-2 py-1.5 rounded-lg flex gap-1 items-start font-sans">
                                    <span>💡</span>
                                    <span><strong>LCA Milestone:</strong> Widely cited global benchmark paper establishing environmental performance boundaries for algae biofuels.</span>
                                </div>
                            </div>

                            <!-- Item 2 -->
                            <div class="bg-white border border-slate-200/80 hover:border-st-blue/30 rounded-xl p-4 transition-all hover:shadow-md card-3d-bevel">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="bg-sky-100 text-sky-800 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase font-mono tracking-widest">
                                        ★ Highly Cited (400+ Cites)
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">2011</span>
                                </div>
                                <p class="text-[12px] text-slate-700 font-medium leading-relaxed mt-2">
                                    Kumar, D., Murthy, G. S. (2011). Impact of pretreatment and downstream processing technologies on economics and energy in cellulosic ethanol production. <strong>Biotechnology for Biofuels</strong> 4 (1), 27.
                                </p>
                                <div class="mt-2 text-[10px] text-[#0d56a6] bg-[#0d56a6]/5 px-2 py-1.5 rounded-lg flex gap-1 items-start font-sans">
                                    <span>📊</span>
                                    <span><strong>Bioprocess Economics:</strong> Foundational techno-economic assessment mapping cellulosic ethanol pre-treatment limits.</span>
                                </div>
                            </div>

                            <!-- Item 3 -->
                            <div class="bg-white border border-slate-200/80 hover:border-st-orange/30 rounded-xl p-4 transition-all hover:shadow-md card-3d-bevel">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="bg-indigo-100 text-indigo-805 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase font-mono tracking-widest">
                                        ★ Licensed Commercial Patent
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">2011</span>
                                </div>
                                <p class="text-[12px] text-slate-700 font-medium leading-relaxed mt-2">
                                    Murthy, G. S., Singh, V. (2011). Dynamic fermentation controller. <strong>US Patent 7,862,992.</strong>
                                </p>
                                <div class="mt-2 text-[10px] text-[#0d56a6] bg-[#0d56a6]/5 px-2 py-1.5 rounded-lg flex gap-1 items-start font-sans">
                                    <span>⚙️</span>
                                    <span><strong>Industrial Scaling:</strong> Deployed in multiple industrial dry-grind corn ethanol biorefineries across the US.</span>
                                </div>
                            </div>

                            <!-- Item 4 -->
                            <div class="bg-white border border-slate-200/80 hover:border-st-green/30 rounded-xl p-4 transition-all hover:shadow-md card-3d-bevel">
                                <div class="flex justify-between items-start gap-2">
                                    <span class="bg-green-100 text-green-800 text-[9px] font-bold px-2 py-0.5 rounded-full uppercase font-mono tracking-widest">
                                        ★ High-Impact Model (150+ Cites)
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">2013</span>
                                </div>
                                <p class="text-[12px] text-slate-700 font-medium leading-relaxed mt-2">
                                    Kumar, D., Murthy, G. S. (2013). Stochastic molecular model of enzymatic hydrolysis of cellulose for ethanol production. <strong>Biotechnology for Biofuels</strong> 6 (1), 63.
                                </p>
                                <div class="mt-2 text-[10px] text-[#0d56a6] bg-[#0d56a6]/5 px-2 py-1.5 rounded-lg flex gap-1 items-start font-sans">
                                    <span>🧬</span>
                                    <span><strong>Molecular Cybernetics:</strong> Advanced stochastic simulation of multi-enzyme synergistic cellulose hydrolysis.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 pt-1">
                        <!-- GS DISSERTATIONS -->
                        <div id="sec-dissertations" class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest font-mono border-l-2 border-st-orange pl-2">Graduate Dissertations</h4>
                            <div class="space-y-3">
                                <?php foreach ($dissertations as $diss): ?>
                                    <div class="p-4 rounded-xl border border-slate-200 card-3d-bevel space-y-1.5 bg-gradient-to-br from-white to-slate-50/40 relative overflow-hidden flex gap-3 items-start pub-searchable-item" data-search-text="<?= htmlspecialchars(strtolower($diss['title'] . ' ' . $diss['author'] . ' ' . $diss['institution'] . ' ' . $diss['year'])) ?>">
                                        <div class="p-1.5 px-2 bg-st-orange/15 text-st-orange font-bold text-[10px] rounded font-mono shrink-0">
                                            <?= $diss['year'] ?>
                                        </div>
                                        <div>
                                            <h5 class="text-xs sm:text-[13px] font-bold text-[#0c2340] leading-snug">"<?= $diss['title'] ?>"</h5>
                                            <p class="text-[11px] text-slate-600 font-sans mt-1">Author: <strong class="font-bold"><?= $diss['author'] ?></strong></p>
                                            <p class="text-[10px] text-slate-500 font-sans italic">University: <?= $diss['institution'] ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- GS PATENTS -->
                        <div id="sec-patents" class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest font-mono border-l-2 border-st-blue pl-2">Patents & Innovations</h4>
                            <div class="space-y-3">
                                <?php foreach ($patents as $pat): ?>
                                    <div class="p-4 rounded-xl border border-slate-200 card-3d-bevel space-y-2 bg-gradient-to-br from-white to-slate-50/40 relative overflow-hidden flex gap-3 items-start pub-searchable-item" data-search-text="<?= htmlspecialchars(strtolower($pat['title'] . ' ' . $pat['inventors'] . ' ' . $pat['number'] . ' ' . $pat['date'] . ' ' . $pat['office'] . ' ' . ($pat['details'] ?? ''))) ?>">
                                        <div class="p-1.5 px-2 bg-st-blue/15 text-st-blue font-bold text-[10px] rounded font-mono shrink-0 uppercase">
                                            IP
                                        </div>
                                        <div class="space-y-1 w-full">
                                            <h5 class="text-xs sm:text-[13px] font-bold text-[#0c2340] leading-snug">"<?= $pat['title'] ?>"</h5>
                                            <p class="text-[11px] text-slate-600 font-sans">Inventors: <strong class="font-bold"><?= $pat['inventors'] ?></strong></p>
                                            <p class="text-[10px] text-slate-500 font-mono"><?= $pat['number'] ?> • <span class="bg-slate-200 text-slate-800 px-1 py-0.2 rounded text-[9.5px] font-bold"><?= $pat['date'] ?></span></p>
                                            <p class="text-[10px] text-slate-500 font-sans"><?= $pat['office'] ?></p>
                                            <?php if (isset($pat['details'])): ?>
                                                <div class="mt-2 text-[10px] text-[#0c2340] bg-st-orange/5 border border-st-orange/10 p-2 rounded-lg font-sans leading-relaxed">
                                                    💡 <strong>Impact:</strong> <?= $pat['details'] ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- GS JOURNALS -->
                        <div id="sec-journals" class="space-y-3 pt-2">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest font-mono border-l-2 border-st-green pl-2">Journal Papers</h4>
                            
                            <!-- Google Scholar Link Banner -->
                            <div class="bg-[#0b223f]/5 border border-[#0b223f]/15 rounded-xl p-3.5 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-[#0b223f]/10 text-[#0b223f] rounded-lg shrink-0">
                                        <i data-lucide="graduation-cap" class="h-5 w-5"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-[#0b223f] uppercase tracking-wider font-sans">Prof. Ganti S. Murthy's Citations</h4>
                                        <p class="text-[11px] text-slate-500 font-sans">Access full citation metrics, h-index, and real-time research updates on Google Scholar.</p>
                                    </div>
                                </div>
                                <a
                                    href="https://scholar.google.com/citations?user=PInw77gAAAAJ&hl=en"
                                    target="_blank"
                                    rel="noreferrer"
                                    referrerPolicy="no-referrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0b223f] hover:bg-st-orange text-white rounded-lg text-xs font-extrabold transition-all hover:shadow-md cursor-pointer shrink-0"
                                >
                                    <span>Google Scholar Profile</span>
                                </a>
                            </div>

                            <div class="space-y-2">
                                <?php foreach ($journal_papers as $pub): ?>
                                    <div class="p-3.5 rounded-xl border border-slate-200 card-3d-bevel flex gap-3 items-start bg-slate-50/50 hover:bg-white duration-200 transition-all pub-searchable-item" data-search-text="<?= htmlspecialchars(strtolower($pub['citation'] . ' ' . $pub['year'])) ?>">
                                        <div class="p-1 px-1.5 bg-st-green/10 text-st-green border border-st-green/15 rounded text-[10px] font-bold font-mono shrink-0">
                                            <?= $pub['year'] ?>
                                        </div>
                                        <div class="space-y-1.5 flex-1">
                                            <p class="text-[11.5px] text-slate-700 leading-relaxed font-sans mt-0.5">
                                                <?= $pub['citation'] ?>
                                            </p>
                                            <?php if (isset($pub['doi'])): ?>
                                                <div class="flex flex-wrap gap-2 pt-1 items-center">
                                                    <a
                                                        href="https://doi.org/<?= htmlspecialchars($pub['doi']) ?>"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        referrerPolicy="no-referrer"
                                                        class="text-[10px] bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded px-1.5 py-0.5 font-mono text-slate-500 hover:text-slate-700 transition-colors inline-block"
                                                    >
                                                        DOI: <?= htmlspecialchars($pub['doi']) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- GS BOOKS -->
                        <div id="sec-books" class="space-y-3 pt-4 border-t border-slate-100/50 mt-4">
                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest font-mono border-l-2 border-indigo-500 pl-2">Books & Book Chapters</h4>
                            <div class="space-y-2">
                                <?php foreach ($books_and_chapters as $pub): ?>
                                    <div class="p-3.5 rounded-xl border border-slate-200 card-3d-bevel flex gap-3 items-start bg-slate-50/50 hover:bg-white duration-200 transition-all pub-searchable-item" data-search-text="<?= htmlspecialchars(strtolower($pub['citation'] . ' ' . $pub['year'])) ?>">
                                        <div class="p-1 px-1.5 bg-indigo-50 text-indigo-705 border border-indigo-200 rounded text-[10px] font-bold font-mono shrink-0">
                                            <?= $pub['year'] ?>
                                        </div>
                                        <div class="space-y-1.5 flex-1">
                                            <p class="text-[11.5px] text-slate-700 leading-relaxed font-sans mt-0.5">
                                                <?= $pub['citation'] ?>
                                            </p>
                                            <?php if (isset($pub['doi'])): ?>
                                                <div class="flex flex-wrap gap-2 pt-1 items-center">
                                                    <a
                                                        href="https://doi.org/<?= htmlspecialchars($pub['doi']) ?>"
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        referrerPolicy="no-referrer"
                                                        class="text-[10px] bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded px-1.5 py-0.5 font-mono text-slate-500 hover:text-slate-700 transition-colors inline-block"
                                                    >
                                                        DOI: <?= htmlspecialchars($pub['doi']) ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- LEARNING RESOURCES PANE -->
                <div id="pane-learning" class="tab-pane hidden space-y-8 animate-tabFadeIn">
                    <!-- Intro banner -->
                    <div class="bg-gradient-to-br from-st-green/5 via-slate-50 to-st-blue/5 border border-slate-200 rounded-2xl p-6 sm:p-7 space-y-4 shadow-sm">
                        <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-st-green text-white shadow-xs">
                            Academic Materials & Syllabi
                        </span>
                        <h3 class="text-xl sm:text-2xl font-extrabold text-[#0d56a6] tracking-tight uppercase">
                            Learning Resources
                        </h3>
                        <p class="text-slate-700 leading-relaxed text-xs sm:text-[13px] font-sans">
                            Open-source lecture materials, course directories, and academic syllabi from our ongoing and past teaching programs across institutions.
                        </p>
                    </div>

                    <!-- Courses Grid/List -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                        <!-- 1. Biofuels Production Technologies -->
                        <div class="bg-white border border-slate-200 hover:border-st-orange/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between card-3d-bevel">
                            <div class="space-y-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-orange/10 text-st-orange rounded-lg">
                                        <i data-lucide="zap" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">1. Biofuels Production Technologies</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">Foundational Course</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    A foundational course exploring traditional and advanced biofuel generation methodologies.
                                </p>
                                
                                <div class="space-y-2 pt-1 font-sans">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                                        <span class="font-bold text-st-orange font-mono">🔗</span>
                                        <span class="font-sans">Lecture Slides & Notes:</span>
                                    </div>
                                    <a 
                                        href="https://github.com/gsnmurthy/Biofuels_LectureSlides_Notes" 
                                        target="_blank" 
                                        rel="noreferrer" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-st-orange hover:text-white transition-all text-[11px] text-[#0d56a6] rounded-md font-bold font-sans cursor-pointer hover:shadow-sm"
                                    >
                                        <i data-lucide="external-link" class="h-3 w-3"></i>
                                        Biofuels Lecture Slides & Notes Repository
                                    </a>
                                </div>

                                <div class="space-y-2 pt-1 border-t border-slate-100 font-sans">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                                        <span class="font-bold text-st-orange font-mono">📺</span>
                                        <span class="font-sans">Media Feature:</span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p class="text-[11px] text-slate-500 italic font-sans animate-fadeIn">Watch a brief conceptual overview of biofuels on the Green Science Oregon YouTube Channel (segment begins at 2:30 min).</p>
                                        <a 
                                            href="https://www.youtube.com/watch?v=Nib8f1Wgx3M" 
                                            target="_blank" 
                                            rel="noreferrer" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-st-orange hover:text-white transition-all text-[11px] text-[#0d56a6] rounded-md font-bold font-sans cursor-pointer hover:shadow-sm"
                                        >
                                            <i data-lucide="play" class="h-3 w-3"></i>
                                            Green Science Oregon YouTube Channel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Ecological Systems Sustainability -->
                        <div class="bg-white border border-slate-200 hover:border-st-orange/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 flex flex-col justify-between card-3d-bevel">
                            <div class="space-y-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-blue/10 text-st-blue rounded-lg">
                                        <i data-lucide="sprout" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">2. Ecological Systems Sustainability</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">Systems Analysis of Biofuels and Bioproducts</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    An interdisciplinary curriculum focusing on resource mapping, life cycle assessments (LCA), and techno-economic modeling within the food-energy-water nexus.
                                </p>

                                <div class="space-y-2 pt-1 font-sans">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                                        <span class="font-bold text-st-blue font-mono">🔗</span>
                                        <span class="font-sans">Lecture Slides on GitHub:</span>
                                    </div>
                                    <a 
                                        href="https://github.com/gsnmurthy/SystemsAnalysis_Course" 
                                        target="_blank" 
                                        rel="noreferrer" 
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-st-blue hover:text-white transition-all text-[11px] text-[#0d56a6] rounded-md font-bold font-sans cursor-pointer hover:shadow-sm"
                                    >
                                        <i data-lucide="external-link" class="h-3 w-3"></i>
                                        Systems Analysis Course Repository
                                    </a>
                                </div>

                                <div class="space-y-2 pt-1 border-t border-slate-100 font-sans">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                                        <span class="font-bold text-st-blue font-mono">📺</span>
                                        <span class="font-sans">Video Lectures:</span>
                                    </div>
                                    <div class="space-y-1.5">
                                        <p class="text-[11px] text-slate-500 italic font-sans">Stream full programmatic lecture recordings.</p>
                                        <a 
                                            href="https://www.youtube.com/playlist?list=PLM8HMFlrLIScXW7p_9Pi_Fedjmc5J2uwR" 
                                            target="_blank" 
                                            rel="noreferrer" 
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-st-blue hover:text-white transition-all text-[11px] text-[#0d56a6] rounded-md font-bold font-sans cursor-pointer hover:shadow-sm"
                                        >
                                            <i data-lucide="play" class="h-3 w-3"></i>
                                            Systems Analysis YouTube Playlist
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Bioprocess Control Systems -->
                        <div class="bg-white border border-slate-200 hover:border-st-green/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-green/10 text-st-green rounded-lg">
                                        <i data-lucide="cpu" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">3. Bioprocess Control Systems</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">Advanced Curriculum</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    An advanced curriculum treating the implementation of classic and advanced process control algorithms (such as model predictive control) to monitor and optimize biomanufacturing and cellular environments.
                                </p>
                            </div>
                        </div>

                        <!-- 4. Ecological Engineering Design -->
                        <div class="bg-white border border-slate-200 hover:border-st-orange/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-orange/10 text-st-orange rounded-lg">
                                        <i data-lucide="layers" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">4. Ecological Engineering Design</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">Senior Capstone / Oregon State University</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    A senior capstone design curriculum developed and co-taught alongside <strong>Prof. John Selker</strong> at Oregon State University, tasking engineering students with solving complex, multi-variable environmental and resource-nexus challenges.
                                </p>
                            </div>
                        </div>

                        <!-- 5. Bioprocess Engineering and Technology (BSE 624) -->
                        <div class="bg-white border border-slate-200 hover:border-st-green/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-green/10 text-st-green rounded-lg">
                                        <i data-lucide="graduation-cap" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">5. Bioprocess Engineering and Technology</h4>
                                        <p class="text-[10px] text-slate-400 font-mono font-sans font-light">IIT Indore / BSBE Department</p>
                                    </div>
                                </div>
                                <p class="text-slate-605 text-xs leading-relaxed font-sans">
                                    A core graduate-level course examining the fundamentals of bioprocess technology, upstream and downstream cultivation metrics, material and energy process calculations, bioreactor control, and fermentation economics.
                                </p>
                            </div>
                        </div>

                        <!-- 6. Scientific Research Methodology -->
                        <div class="bg-white border border-slate-200 hover:border-st-blue/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-blue/10 text-st-blue rounded-lg">
                                        <i data-lucide="atom" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">6. Scientific Research Methodology</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">Core Graduate-Level Course / IIT Indore</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    A core graduate-level course focusing on rigorous experimental design, data validation, statistical uncertainty quantification, research ethics, and effective technical communication protocols for peer-reviewed engineering documentation.
                                </p>
                            </div>
                        </div>

                        <!-- 7. ChE 2035 Transport Phenomena -->
                        <div class="bg-white border border-slate-200 hover:border-st-orange/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-orange/10 text-st-orange rounded-lg">
                                        <i data-lucide="layers" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">7. Transport Phenomena</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">IIT Indore / Chemical Engineering Department</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    The main aim of this course is to provide a detailed understanding of Transport Phenomena. The learning outcomes for this course are: 1) Demonstrate a thorough understanding of the fundamental principles underlying mass transfer, momentum transfer, and heat transfer. 2) Apply transport phenomena concepts to the scale-up and design of chemical processes and equipment, ensuring that laboratory-scale findings are accurately translated to industrial operations. 3) Develop the ability to formulate and solve mathematical models representing transport processes.
                                </p>
                            </div>
                        </div>

                        <!-- 8. BSE 641 Engineered Systems Analysis -->
                        <div class="bg-white border border-slate-200 hover:border-st-blue/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-blue/10 text-st-blue rounded-lg">
                                        <i data-lucide="sprout" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">8. Engineered Systems Analysis</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">IIT Indore / BSBE Department</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    Assessing sustainability of engineered systems is integral part of engineering practice. This course will introduce multiple dimensions of sustainability analysis for engineered systems. Methods and tools to perform technical feasibility and economic viability analysis, environmental risk, resource sustainability and life cycle assessments will be discussed in lectures and case studies.
                                </p>
                            </div>
                        </div>

                        <!-- 9. BSE 621 Modeling and Controls in Medical Devices -->
                        <div class="bg-white border border-slate-200 hover:border-st-green/30 rounded-xl p-5 hover:shadow-md transition-all duration-300 card-3d-bevel">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="p-2 bg-st-green/10 text-st-green rounded-lg">
                                        <i data-lucide="cpu" class="h-4 w-4"></i>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-extrabold text-[#0d56a6] uppercase tracking-wide font-sans">9. Modeling and Controls in Medical Devices</h4>
                                        <p class="text-[10px] text-slate-400 font-mono">IIT Indore / BSBE Department</p>
                                    </div>
                                </div>
                                <p class="text-slate-600 text-xs leading-relaxed font-sans">
                                    This course covers the principles and concepts of control systems and estimation theory for biomedical systems and devices. This course provides an introduction to relevant biomedical applications of the control systems and estimation techniques.
                                </p>
                            </div>
                        </div>

                    </div>

                    <!-- Talks & Public Seminars section -->
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 sm:p-8 space-y-6 shadow-xs">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/80 pb-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-st-orange">
                                    <span class="p-1 px-2.5 bg-st-orange/10 text-st-orange text-[10px] font-bold uppercase rounded-md tracking-wider border border-st-orange/20 font-sans">
                                        IKS & Scientific Outreach
                                    </span>
                                </div>
                                <h3 class="text-xl font-extrabold uppercase tracking-tight text-slate-900 font-sans mt-2">
                                    Talks & Public Seminars
                                </h3>
                                <p class="text-xs text-slate-500 font-sans font-light">
                                    Public lectures, international guest keynotes, and media broadcasts delivered by Prof. Ganti Suryanarayana Murthy.
                                </p>
                            </div>
                        </div>

                        <!-- Filtering + Search bar -->
                        <div class="flex flex-col lg:flex-row gap-4 justify-between items-stretch lg:items-center">
                            <!-- Tabs -->
                            <div class="flex flex-wrap gap-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200/80 self-start">
                                <button onclick="filterPiTalks('invited')" id="talk-btn-invited" class="talk-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer bg-st-orange text-white shadow-xs">
                                    Invited Lectures (24)
                                </button>
                                <button onclick="filterPiTalks('live')" id="talk-btn-live" class="talk-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-slate-600 hover:bg-slate-200 hover:text-slate-900">
                                    Live Panels (6)
                                </button>
                            </div>

                            <!-- Search -->
                            <div class="relative max-w-sm w-full">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-sans">
                                    <i data-lucide="search" class="h-3.5 w-3.5"></i>
                                </span>
                                <input
                                    id="talk-search-input"
                                    type="text"
                                    placeholder="Search talks & seminars..."
                                    oninput="searchPiTalks()"
                                    class="w-full bg-white border border-slate-200 rounded-xl py-1.5 pl-9 pr-4 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-st-orange/50 focus:border-st-orange/50 font-sans"
                                />
                            </div>
                        </div>

                        <!-- Grid of video list -->
                        <div id="pi-talks-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php 
                            $pi_talks = [
                                ["title" => "Awakening The Bharatiya Mind: IKS, Colonialism & The Dharma Paradigm", "url" => "https://youtu.be/NKQT08JlI5M", "category" => "invited"],
                                ["title" => "Vision for the Future of Indian Knowledge Systems: A 30-Year Plan", "url" => "https://youtu.be/hRrAd_pY5kQ", "category" => "invited"],
                                ["title" => "Interview: Prof. Ganti Suryanarayana Murthy, National Coordinator, IKS", "url" => "https://youtu.be/xrWqYUVOEDA", "category" => "invited"],
                                ["title" => "Introduction to Dhara Series | Vision Ayurveda 2047", "url" => "https://youtu.be/FedqIuFbdQA", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 1", "url" => "https://youtu.be/5W8Wzx_6LTw", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 2", "url" => "https://youtu.be/b965BddtPkE", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 3", "url" => "https://youtu.be/MkBlUW2C-jU", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 4", "url" => "https://youtu.be/AgkpNGFrL-4", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 5", "url" => "https://youtu.be/vHTGri4qQes", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 6", "url" => "https://youtu.be/BGqobT_96xM", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 7", "url" => "https://youtu.be/EdbFCmF-AgA", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 8", "url" => "https://youtu.be/gZSp4XsyZN0", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 9", "url" => "https://youtu.be/LILM4Aq6-pQ", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 10", "url" => "https://youtu.be/xz8F36_TtuU", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 11", "url" => "https://youtu.be/NyjcPtotVmE", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 12", "url" => "https://youtu.be/GSK-ws5u8zc", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 13", "url" => "https://youtu.be/BM3zcSxc8CY", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 14", "url" => "https://youtu.be/FY-we_3w0z0", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 15", "url" => "https://youtu.be/MDr61aQtFQs", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 16", "url" => "https://youtu.be/sKYS8jNXs2s", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 17", "url" => "https://youtu.be/RHabkg7VNXQ", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 18", "url" => "https://youtu.be/p9dhYDnTi5g", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 19", "url" => "https://youtu.be/z4kxz5OkF4U", "category" => "invited"],
                                ["title" => "Invited Talk / Seminar Video 20", "url" => "https://youtu.be/ozCjEJPQHuM", "category" => "invited"],
                                ["title" => "Live Streamed Panel / Event 1", "url" => "https://www.youtube.com/live/Fy9oy1fRxv0", "category" => "live"],
                                ["title" => "Live Streamed Panel / Event 2", "url" => "https://www.youtube.com/live/bhSI5zDTj1s", "category" => "live"],
                                ["title" => "Live Streamed Panel / Event 3", "url" => "https://www.youtube.com/live/fax3GuCFVX4", "category" => "live"],
                                ["title" => "Live Streamed Panel / Event 4", "url" => "https://www.youtube.com/live/mBq2gF7p8U4", "category" => "live"],
                                ["title" => "Live Streamed Panel / Event 5", "url" => "https://www.youtube.com/live/KedgMEsrKYI", "category" => "live"],
                                ["title" => "Live Streamed Panel / Event 6", "url" => "https://www.youtube.com/live/tj-FgzDZty4", "category" => "live"]
                            ];

                            foreach ($pi_talks as $idx => $talk): 
                                $isLive = $talk['category'] === 'live';
                                $icon = $isLive ? 'radio' : 'video';
                                $tagStyle = $isLive ? 'bg-sky-55 text-sky-700 bg-sky-500/10 border-sky-500/20' : 'bg-amber-55 text-amber-700 bg-amber-500/10 border-amber-500/20';
                                $tagLabel = $isLive ? 'Live Session' : 'Invited Seminar';
                            ?>
                                <div 
                                    class="pi-talk-card bg-white hover:bg-slate-55/40 border border-slate-200/80 hover:border-st-orange/20 rounded-xl p-4 transition-all duration-300 flex flex-col justify-between gap-3 group relative overflow-hidden card-3d-bevel"
                                    data-category="<?= $talk['category'] ?>"
                                    data-title="<?= strtolower(htmlspecialchars($talk['title'])) ?>"
                                >
                                    <div class="space-y-2 font-sans">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-bold uppercase rounded-md border tracking-wider font-mono <?= $tagStyle ?>">
                                                <i data-lucide="<?= $icon ?>" class="h-3 w-3"></i>
                                                <?= $tagLabel ?>
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-mono">#<?= $idx + 1 ?></span>
                                        </div>
                                        <h4 class="text-xs sm:text-[13px] font-bold text-slate-800 group-hover:text-st-orange transition-colors duration-200 leading-snug font-sans">
                                            <?= $talk['title'] ?>
                                        </h4>
                                    </div>

                                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between overflow-hidden">
                                        <span class="text-[10px] text-slate-400 font-mono truncate max-w-[150px] sm:max-w-xs"><?= $talk['url'] ?></span>
                                        <a 
                                            href="<?= $talk['url'] ?>" 
                                            target="_blank" 
                                            rel="noopener noreferrer" 
                                            class="inline-flex items-center gap-1 px-3 py-1 text-[10px] font-bold bg-slate-100 hover:bg-st-orange text-slate-700 hover:text-white rounded-lg transition-all duration-200 border border-slate-200 hover:border-transparent cursor-pointer shadow-xs whitespace-nowrap font-sans"
                                        >
                                            <span>Watch</span>
                                            <i data-lucide="play" class="h-2.5 w-2.5 shrink-0"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- No Results State -->
                        <div id="pi-talks-no-results" class="hidden text-center py-12 bg-white border border-slate-200 rounded-xl font-sans font-light">
                            <p class="text-sm text-slate-400 italic">No matching public talks found in this category.</p>
                        </div>
                    </div>

                </div>

                <!-- GALLERY PANE -->
                <div id="pane-gallery" class="tab-pane hidden space-y-6 animate-tabFadeIn">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-st-green">
                            <span class="p-1 px-2.5 bg-st-green/10 text-st-green text-[10px] font-bold uppercase rounded-md tracking-wider border border-st-green/20">
                                Visual Archive
                            </span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold uppercase tracking-tight text-[#0c2340]">
                            The Place of Memories
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-3xl leading-relaxed font-sans font-light">
                            A serene, clean canvas dedicated to preserving handpicked milestones, academic highlights, and collaborative moments of STLab researchers at IIT Indore.
                        </p>
                    </div>

                    <!-- Core Memory Visual Card -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl p-6 md:p-8 space-y-6 shadow-xs card-3d-bevel">
                        <div class="text-center max-w-xl mx-auto space-y-3 pb-2">
                            <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-st-green/10 text-st-green mb-1">
                                <i data-lucide="sparkles" class="h-6 w-6 animate-pulse text-st-green"></i>
                            </div>
                            <h4 class="text-base font-extrabold text-slate-800">A Repository of Shared Journeys</h4>
                            <p class="text-xs text-slate-500 leading-relaxed font-sans">
                                This sanctuary serves as a curated archive for key snapshots of our laboratory halls, summits, and academic events.
                            </p>
                        </div>

                        <!-- Curated Memories Board Grid -->
                        <div class="space-y-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-2 gap-2">
                                <h5 id="php-memories-count-header" class="text-[10.5px] font-bold text-slate-400 uppercase tracking-widest">
                                    Archived Board Snapshots
                                </h5>
                                <span class="text-[10.5px] text-slate-400 italic">
                                    Photo Courtesy: Abhishek D Kalbande and Buddhodev Ghosh
                                </span>
                            </div>

                            <div id="php-memories-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php
                                $static_memories = [
                                    [
                                        "id" => "7", 
                                        "url" => "assets/Thesis_Submission_of_Mr_Abhishek_D_Kalbande.jpeg", 
                                        "year" => "2026",
                                        "fileName" => "Thesis_Submission_of_Mr_Abhishek_D_Kalbande",
                                        "displayName" => "STLab's Student, Mr Abhishek D. Kalbande, is submitting his MSc. Thesis",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "8", 
                                        "url" => "assets/Prof_Murthy_with_Foreign_Delegate_and_Prof_Sarkar.jpeg", 
                                        "year" => "2026",
                                        "fileName" => "Prof_Murthy_with_Foreign_Delegate_and_Prof_Sarkar",
                                        "displayName" => "Prof. Murthy with Prof. Mark Wilkins, Kansas State Univ. and Prof. Sarkar (PIC, CRDT)",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "12", 
                                        "url" => "assets/Prof._Murthys_Birthday_2025.jpg", 
                                        "year" => "2025",
                                        "fileName" => "Prof._Murthys_Birthday_2025",
                                        "displayName" => "STLab team celebrating Prof. Murthy's Birthday",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "1", 
                                        "url" => "assets/Pre-Holi_Celebration_with_Prof_Murthy.jpeg", 
                                        "year" => "2026",
                                        "fileName" => "Pre_Holi_Celebration_with_Prof_Murthy",
                                        "displayName" => "STLab researchers enjoying Pre-Holi Celebration",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "2", 
                                        "url" => "assets/Holi_Celebration_with_Prof_Murthy.jpeg", 
                                        "year" => "2026",
                                        "fileName" => "Holi_Celebration_with_Prof_Murthy",
                                        "displayName" => "STLab members celebrating Holi festival",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "3", 
                                        "url" => "assets/Chaye_Pe_Charcha.jpeg", 
                                        "year" => "2025",
                                        "fileName" => "Chaye_Pe_Charcha",
                                        "displayName" => "STLab researchers discussing core ideas over Chaye Pe Charcha",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "4", 
                                        "url" => "assets/Dinner_with_Prof_Murthy.jpeg", 
                                        "year" => "2025",
                                        "fileName" => "Dinner_with_Prof_Murthy",
                                        "displayName" => "STLab team sharing a pleasant dinner",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "5", 
                                        "url" => "assets/Learning_Doesnt_stop_with_Prof_Murthy.jpeg", 
                                        "year" => "2025",
                                        "fileName" => "Learning_Doesnt_stop_with_Prof_Murthy",
                                        "displayName" => "STLab scholars engaged in interactive learning",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "6", 
                                        "url" => "assets/Sky_Exploration_with_Prof_Murthy.jpeg", 
                                        "year" => "2026",
                                        "fileName" => "Sky_Exploration_with_Prof_Murthy",
                                        "displayName" => "STLab members exploring the telescope during Sky Exploration",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "9", 
                                        "url" => "assets/STL_Labs_Doctoral_Students_with_beloved_Prof.jpeg", 
                                        "year" => "2025",
                                        "fileName" => "STL_Labs_Doctoral_Students_with_beloved_Prof",
                                        "displayName" => "STLab's Students with Prof. Murthy (Group Snapshot)",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "11", 
                                        "url" => "assets/Teachers_Day_Celebration_2025.jpeg", 
                                        "year" => "2025",
                                        "fileName" => "Teachers_Day_Celebration_2025",
                                        "displayName" => "STLab scholars hosting a surprise Teacher's Day Celebration",
                                        "errorNotes" => ""
                                    ],
                                    [
                                        "id" => "10", 
                                        "url" => "assets/STL_POD_1B-601.jpeg", 
                                        "year" => "2025",
                                        "fileName" => "STL_POD_1B-601",
                                        "displayName" => "STLab team at work inside the state-of-the-art STL POD 1B-601",
                                        "errorNotes" => ""
                                    ]
                                ];
                                foreach ($static_memories as $m):
                                ?>
                                <div class="bg-white border border-slate-200 rounded-xl p-3.5 flex flex-col gap-3 relative overflow-hidden shadow-sm group hover:-rotate-1 hover:scale-[1.01] transition-transform duration-300 h-full">
                                    <div class="relative aspect-[4/3] w-full rounded-lg overflow-hidden bg-slate-900 shadow-inner flex items-center justify-center">
                                        <img 
                                            src="<?= $m['url'] ?>" 
                                            alt="<?= $m['displayName'] ?>" 
                                            class="w-full h-full object-cover transition-transform duration-350 group-hover:scale-105"
                                            referrerPolicy="no-referrer"
                                        />
                                        <div class="absolute top-2 right-2 px-2 py-0.5 bg-black/75 backdrop-blur-xs text-[10px] font-bold text-white rounded-md tracking-wider">
                                            <?= $m['year'] ?>
                                        </div>
                                    </div>
                                    <div class="pt-2.5 border-t border-slate-100 flex flex-col gap-1">
                                        <div class="font-extrabold text-xs text-slate-800 leading-tight text-left">
                                            <?= $m['displayName'] ?>
                                        </div>
                                        <div class="flex flex-col gap-0.5 text-[10px] text-slate-400 font-mono text-left">
                                            <?php if (!empty($m['errorNotes'])): ?>
                                            <span class="text-emerald-600 font-bold font-sans text-[9px] flex items-center gap-0.5 mt-0.5 animate-pulse">
                                                <i data-lucide="check" class="h-3 w-3 shrink-0 text-emerald-500"></i>
                                                <?= $m['errorNotes'] ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTACT PANE -->
                <div id="pane-contact" class="tab-pane hidden space-y-6 animate-tabFadeIn">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-st-orange">
                            <span class="p-1 px-2.5 bg-st-orange/10 text-st-orange text-[10px] font-bold uppercase rounded-md tracking-wider border border-st-orange/20">
                                Contact Channel
                            </span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold uppercase tracking-tight text-[#0c2340]">
                            Contact STLab Academic Core
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-3xl leading-relaxed font-sans font-light">
                            Reach out for academic collaborations, research inquiries, or PhD projects submissions.
                        </p>
                    </div>

                    <?php if ($feedbackMessage): ?>
                        <div class="p-4 rounded-xl border text-xs font-semibold <?= $feedbackClass ?>">
                            <?= $feedbackMessage ?>
                        </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch pt-2">
                        
                        <!-- Address section -->
                        <div class="lg:col-span-8 bg-[#0c2340] rounded-2xl p-6 sm:p-8 text-white flex flex-col justify-between space-y-6 shadow-md">
                            <div class="space-y-4">
                                <span class="text-st-orange text-[10px] font-bold uppercase tracking-wider block">Lab Headquarters Address</span>
                                <div class="flex gap-4 items-start text-xs leading-relaxed font-sans">
                                    <i data-lucide="map-pin" class="h-6 w-6 text-st-orange shrink-0 mt-0.5 animate-bounce"></i>
                                    <div>
                                        <h4 class="font-extrabold text-[#ffa34d] text-sm sm:text-base">Sustainable Technologies Laboratory (STLab)</h4>
                                        <p class="text-slate-300 mt-1 sm:text-[13px]">601-B POD Building</p>
                                        <p class="text-slate-300 sm:text-[13px]">Indian Institute of Technology Indore</p>
                                        <p class="text-slate-300 sm:text-[13px]">Simrol Campus, Khandwa Road, Simrol</p>
                                        <p class="text-slate-300 sm:text-[13px]">Indore, Madhya Pradesh 453552, India</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Picture of our lab in the middle of Physical Address section -->
                            <div class="w-full overflow-hidden rounded-xl border border-white/10 shadow-lg relative bg-black/10">
                                <img src="assets/STL_POD_1B-601.jpeg" alt="STLab Physical Laboratory (601-B POD)" class="w-full h-48 sm:h-56 object-cover transform hover:scale-102 transition-all duration-500" />
                                <div class="absolute inset-0 bg-gradient-to-t from-[#0c2340]/90 via-[#0c2340]/10 to-transparent pointer-events-none"></div>
                                <span class="absolute bottom-2.5 left-3 text-[10px] sm:text-[11px] font-medium text-slate-200 bg-[#0c2340]/80 px-3 py-1 rounded-lg backdrop-blur-xs border border-white/5">
                                    STLab Physical Laboratory Room (601-B POD Building)
                                </span>
                            </div>

                            <!-- Direct Email Button -->
                            <div class="pt-2 border-t border-white/10 space-y-3">
                                <a href="mailto:ganti.murthy@iiti.ac.in?subject=STLab%20Portal%20Inquiry" class="inline-flex w-full items-center justify-center gap-2.5 px-6 py-3.5 bg-st-orange hover:bg-[#d95d12] text-white text-xs sm:text-sm font-extrabold rounded-xl transition-all duration-300 shadow-md hover:-translate-y-0.5 transform cursor-pointer">
                                    <i data-lucide="mail" class="h-4.5 w-4.5 animate-pulse"></i>
                                    <span>Open Mail App to Email Professor Ganti Murthy Directly</span>
                                </a>
                                <p class="text-[10px] text-slate-400 text-center leading-normal">
                                    Opens your device's mail app directly to <strong class="text-slate-300">ganti.murthy@iiti.ac.in</strong> (Safe, reliable alternative if forms fail to send).
                                </p>
                            </div>

                            <div class="border-t border-white/10 pt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-slate-300 font-medium">
                                <a href="mailto:ganti.murthy@iiti.ac.in" class="flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer select-all font-mono">
                                    <i data-lucide="mail" class="h-3.5 w-3.5 text-st-orange"></i>
                                    ganti.murthy@iiti.ac.in
                                </a>
                                <span class="flex items-center gap-1.5 hover:text-white transition-colors cursor-pointer font-mono">
                                    <i data-lucide="globe" class="h-3.5 w-3.5 text-st-orange"></i>
                                    iiti.ac.in
                                </span>
                            </div>
                        </div>

                        <!-- Inquiry form -->
                        <div class="lg:col-span-4 bg-slate-50 rounded-2xl border border-slate-200 p-6 sm:p-8 space-y-5 hover:bg-white duration-300 transition-all flex flex-col justify-between">
                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-200 pb-2 flex items-center gap-1.5">
                                    <i data-lucide="award" class="h-4 w-4 text-st-orange"></i>
                                    Academic Inquiries
                                </h4>
                                <div class="text-[11.5px] text-slate-600 space-y-3.5 font-sans leading-relaxed">
                                    <p class="font-medium text-slate-800">If you are a student interested in pursuing doctoral or postdoctoral research under Prof. Ganti S. Murthy, please read the official admissions details on IIT Indore website first.</p>
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 leading-relaxed text-slate-700 shadow-2xs italic font-light">
                                        “Prospective students must send a clear two-page CV. Along with the CV, please send a one page note describing 1) your research interests and experience, 2) why you are interested in a particular area and 3) how can you contribute to the STL research program. Brevity and specificity is prized by us. You are applying to an Engineering program, not a poetry contest, so do not send us AI slop with generic statements. We are interested in working with you as a person. You are more likely to get a response if you do not use AI generated text. Write in your own language however ‘unpolished’ it may sound.”
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Direct Form Submission -->
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-xs mt-3">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider border-b border-slate-200 pb-2 mb-4 flex items-center gap-1.5">
                            <i data-lucide="mail" class="h-4 w-4 text-st-orange"></i>
                            STLab Suggestion & Inquiry Form
                        </h4>
                        
                        <form method="POST" action="index.php?tab=contact" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <input type="hidden" name="action" value="contact_submit" />
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Your Name *</label>
                                <input type="text" name="name" required placeholder="e.g. Rahul Sharma" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs font-medium text-slate-800 focus:border-st-orange outline-none" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Designation or Institute *</label>
                                <input type="text" name="designation" required placeholder="e.g. Researcher, IIT Indore" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs font-medium text-slate-800 focus:border-st-orange outline-none" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Valid Institutional Email *</label>
                                <input type="email" name="email" required placeholder="e.g. student@iiti.ac.in" class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs font-medium text-slate-800 focus:border-st-orange outline-none" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Suggestion or Query *</label>
                                <textarea name="message" rows="4" required placeholder="Please compile your suggestions, process queries, or collaboration questions here..." class="w-full bg-white border border-slate-200 rounded-lg p-2 text-xs font-medium text-slate-800 focus:border-st-orange outline-none"></textarea>
                            </div>
                            <div class="sm:col-span-2 border-t border-slate-100 pt-3">
                                <button type="submit" class="w-full bg-st-orange hover:bg-[#d95d12] text-white text-xs font-bold py-2.5 px-4 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs">
                                    Submit Suggestion to Prof. Ganti Murthy
                                </button>
                                <span class="block text-[9.5px] text-center text-slate-400 mt-2">
                                    Submissions are automatically delivered to <span class="font-semibold text-slate-500">ganti.murthy@iiti.ac.in</span>
                                </span>
                            </div>
                        </form>
                    </div>

                    <!-- Map segment -->
                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-xs mt-4 space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                            <div class="space-y-1">
                                <h4 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                                    <i data-lucide="map-pin" class="h-4 w-4 text-st-orange animate-pulse"></i>
                                    Simrol Campus Google Map
                                </h4>
                                <p class="text-[11px] text-slate-500 font-sans">601-B POD Building, Indian Institute of Technology Indore, Simrol Campus, Khandwa Road, Simrol, Indore, Madhya Pradesh 453552, India</p>
                            </div>
                            <a href="https://maps.app.goo.gl/EP3dxc4qz6pxHgyaA" target="_blank" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold bg-[#0c2340] hover:bg-[#ffa04d] hover:text-[#0c2340] text-white rounded-lg transition-all shadow-sm cursor-pointer transform hover:-translate-y-0.5">
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                Open in Google Maps
                            </a>
                        </div>

                        <!-- Embedded map iframe -->
                        <div class="w-full h-80 rounded-xl overflow-hidden border border-slate-200 shadow-md bg-slate-100 relative">
                            <iframe
                                title="IIT Indore Simrol Google Map"
                                src="https://maps.google.com/maps?q=Indian%20Institute%20of%20Technology%20Indore&amp;t=&amp;z=15&amp;ie=UTF8&amp;iwloc=&amp;output=embed"
                                class="w-full h-full border-0"
                                allowfullscreen
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        </div>
                    </div>

                </div>

                <!-- IITI WEATHER STATISTICS PANE -->
                <div id="pane-weather" class="tab-pane hidden space-y-6 animate-tabFadeIn">
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 text-st-orange">
                            <span class="p-1 px-2.5 bg-st-orange/10 text-st-orange text-[10px] font-bold uppercase rounded-md tracking-wider border border-st-orange/20">
                                Live Station Feed
                            </span>
                        </div>
                        <h3 class="text-xl sm:text-2xl font-extrabold uppercase tracking-tight text-[#0c2340]">
                            IITI Weather Statistics
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 max-w-3xl leading-relaxed font-sans font-light">
                            Explore the weather analytics, telemetry data, and sensor visualization from the weather station hosted directly on the Simrol campus.
                        </p>
                    </div>

                    <!-- Seamless Embed Iframe -->
                    <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-md flex flex-col h-[700px] relative">
                        <div class="bg-slate-50 border-b border-slate-200 px-4 py-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <span class="text-[11px] font-mono text-slate-500 font-bold uppercase tracking-wider">
                                    live.streamlit.app (IIT Indore Streamlit Node)
                                </span>
                            </div>
                            <a 
                                href="https://iitiws-jfmmz866l9.streamlit.app/" 
                                target="_blank" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-st-blue hover:text-white bg-white hover:bg-st-blue border border-slate-200 rounded-lg shadow-2xs transition-all cursor-pointer"
                            >
                                <span>Open in New Tab</span>
                                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                            </a>
                        </div>
                        <iframe
                            title="IITI Weather Statistics"
                            src="https://iitiws-jfmmz866l9.streamlit.app/?embed=true"
                            class="w-full h-full border-0 flex-1 bg-slate-50"
                            allowfullscreen
                            loading="lazy"
                        ></iframe>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- Consistent Branded Footer -->
    <footer class="bg-slate-900 text-white py-10 relative z-10" style="position: relative; z-index: 10;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Sanskrit Philosophical Motto -->
            <div class="flex flex-col items-center text-center pb-6 border-b border-slate-800/80 mb-6 max-w-3xl mx-auto gap-2">
                <span class="text-sm sm:text-base font-extrabold text-[#f17a1f] tracking-widest uppercase font-sanskrit">
                    ज्ञानं प्रसारणाय, सत्यं आत्मशुद्धये
                </span>
                <span class="text-xs text-slate-400 italic max-w-xl">
                    "Knowledge is for sharing, and truth is for self-purification."
                </span>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 border-b border-slate-800 pb-8 mb-8">
                <div class="flex items-center bg-white p-2.5 rounded-xl border border-slate-200 shadow-sm max-h-24">
                    <img src="assets/STL_New_PNG.png" alt="STL Sustainable Technologies Laboratory" class="h-14 sm:h-16 w-auto select-none shrink-0" referrerPolicy="no-referrer" />
                </div>

                <div class="flex flex-col items-start md:items-end gap-3.5">
                    <div class="flex flex-wrap gap-x-8 gap-y-2.5 text-xs text-slate-400 font-medium">
                        <a href="https://iksindia.org/" target="_blank" rel="noopener noreferrer" class="hover:text-[#ffa34d] transition-colors cursor-pointer">IKS & Traditional Frameworks</a>
                        <a href="https://www.energy.gov/cmei/ito/life-cycle-assessment-and-techno-economic-analysis-training" target="_blank" rel="noopener noreferrer" class="hover:text-[#ffa34d] transition-colors cursor-pointer">TEA & LCA Protocols</a>
                        <a href="https://www.eolss.net/Sample-Chapters/C17/E6-58-02-01.pdf" target="_blank" rel="noopener noreferrer" class="hover:text-[#ffa34d] transition-colors cursor-pointer">Bioprocess Control Systems</a>
                        <a href="https://www.energy.gov/sites/default/files/2023-05/beto-02-project-peer-review-biochem-apr-2023-bomble.pdf" target="_blank" rel="noopener noreferrer" class="hover:text-[#ffa34d] transition-colors cursor-pointer">Process Modeling & Simulation</a>
                    </div>
                    
                    <!-- Server Time / Local Clock in the footer-right block -->
                    <div class="flex items-center gap-2 bg-[#06172a] border border-[#112f54]/60 px-3.5 py-1.5 rounded-full shadow-[inset_0_1px_4px_rgba(0,0,0,0.3)] select-none text-[10.5px] font-mono font-bold text-slate-300">
                        <i data-lucide="clock" class="h-3.5 w-3.5 text-st-orange animate-pulse"></i>
                        <span id="live-clock" class="tracking-wider">00:00:00 IST</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center text-[10px] text-slate-500 gap-4 font-sans">
                <div class="flex flex-col sm:flex-row gap-1 sm:gap-4 items-center">
                    <span>&copy; <?php echo date("Y"); ?> Sustainable Technologies Laboratory. All rights reserved.</span>
                    <span class="hidden sm:inline text-slate-700">|</span>
                    <span>Web design and STL logo created by Buddhodev Ghosh</span>
                </div>
                <div class="flex gap-1.5 items-center">
                    <span class="h-1.5 w-1.5 rounded-full bg-st-green inline-block animate-pulse"></span>
                    <span>IIT Indore institutional serving configuration loaded.</span>
                </div>
            </div>

        </div>
    </footer>

    <!-- Lucide Icons Vector Engine Script -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Tab routing variables
        let activeTab = "<?php echo $activeTab; ?>";

        function switchTab(tabId) {
            // Hide all panels
            document.querySelectorAll(".tab-pane").forEach(pane => {
                pane.classList.add("hidden");
            });
            // Show target panel
            const targetPane = document.getElementById("pane-" + tabId);
            if (targetPane) {
                targetPane.classList.remove("hidden");
            }

            // Reset navigation links styling
            document.querySelectorAll(".nav-btn").forEach(btn => {
                btn.className = "nav-btn text-xs font-bold transition-colors uppercase tracking-wider relative py-2 cursor-pointer text-slate-200 hover:text-st-orange";
            });
            document.querySelectorAll(".nav-indicator").forEach(indicator => {
                indicator.classList.remove("w-full");
                indicator.classList.add("w-0");
            });

            // Set active links styling
            const activeBtn = document.getElementById("nav-" + tabId);
            if (activeBtn) {
                activeBtn.className = "nav-btn text-xs font-extrabold transition-colors uppercase tracking-wider relative py-2 cursor-pointer text-st-orange";
                const activeIndicator = activeBtn.querySelector(".nav-indicator");
                if (activeIndicator) {
                    activeIndicator.classList.remove("w-0");
                    activeIndicator.classList.add("w-full");
                }
            }

            // Reset tab quick navigation buttons styling
            document.querySelectorAll("[id^='tab-btn-']").forEach(btn => {
                btn.className = "flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer text-slate-600 hover:bg-slate-200/50 hover:text-st-blue";
            });

            const activeTabBtn = document.getElementById("tab-btn-" + tabId);
            if (activeTabBtn) {
                let colorClass = "bg-st-blue text-white shadow-[0_3px_8px_rgba(12,35,64,0.25)]";
                if (tabId === 'research' || tabId === 'contact') {
                    colorClass = "bg-st-orange text-white shadow-[0_3px_8px_rgba(241,122,31,0.25)]";
                } else if (tabId === 'people' || tabId === 'learning' || tabId === 'weather') {
                    colorClass = "bg-st-green text-white shadow-[0_3px_8px_rgba(43,147,34,0.25)]";
                }
                activeTabBtn.className = `flex items-center gap-1.5 px-5 py-3 rounded-lg text-xs font-bold transition-all cursor-pointer ${colorClass} border-b-2 border-black/10 scale-[1.02]`;
            }

            // Sync URL query state dynamically (without reloading page, for clean routing)
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + tabId;
            window.history.pushState({ path: newUrl }, '', newUrl);

            // Set state tracking variable
            activeTab = tabId;
        }

        // PI Talks state and client-side filter / search logic
        let currentTalkFilter = 'invited';
        function filterPiTalks(val) {
            currentTalkFilter = val;
            
            // Adjust button active classes
            document.querySelectorAll('.talk-filter-btn').forEach(btn => {
                if (btn.id === 'talk-btn-' + val) {
                    btn.className = "talk-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer bg-st-orange text-white shadow-xs";
                } else {
                    btn.className = "talk-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-slate-600 hover:bg-slate-200 hover:text-slate-900";
                }
            });
            
            applyTalksFilter();
        }

        function searchPiTalks() {
            applyTalksFilter();
        }

        function applyTalksFilter() {
            const query = document.getElementById('talk-search-input').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.pi-talk-card');
            let visibleCount = 0;
            
            cards.forEach(card => {
                const category = card.getAttribute('data-category');
                const title = card.getAttribute('data-title');
                
                const matchesFilter = (category === currentTalkFilter);
                const matchesQuery = (!query || title.includes(query));
                
                if (matchesFilter && matchesQuery) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });
            
            const noResults = document.getElementById('pi-talks-no-results');
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }

        const webMembersDb = {
            ganti: {
                name: "Prof. Ganti Suryanarayana Murthy",
                tag: "Laboratory Director & Chair Professor",
                desc: "Directs all operations at the Sustainable Technologies Laboratory. Bridges advanced bioprocess pathways, cyber-physical automation, and translation of the rich heritage of Indian Knowledge Systems (IKS).",
                topic: "Whole-System Systems Engineering (LCA, TEA, Modeling)",
                highlight: "Directing Government of India's IKS division at AICTE & CISKS Head."
            },
            sagnik: {
                name: "Sagnik Mitra",
                tag: "Ph.D. Researcher (Expected Spring 2027)",
                desc: "Focuses on hexadic tank system control, bioprocess automation, and multi-criteria hardware evaluation using advanced MPC variants.",
                topic: "Hexadic Tank System Control & Bioprocess Automation",
                highlight: "Authored leading research on MPC variants for hexadic tank system control in Systems Microbiology and Biomanufacturing.",
                email: "sagnik.mitra@iiti.ac.in"
            },
            kavita: {
                name: "Kavita Singh",
                tag: "Ph.D. Scholar",
                desc: "Engaged in cell strain characterization. Researches secondary metabolic reactions of cellulosic high-solids waste treatment.",
                topic: "C5 Yeast Fermentation kinetics",
                highlight: "Identified novel thermotolerant yeast strain scaling factors."
            },
            neha: {
                name: "Neha Sharma",
                tag: "Postdoctoral Research Associate",
                desc: "Specialist in life cycle tracking and regional carbon balance schemes. Calculates cradle-to-gate impact metrics.",
                topic: "Cradle-to-Gate Life Cycle Assessment (LCA) of Bio-Plastics",
                highlight: "First author on regional carbon accounting protocols."
            },
            amit: {
                name: "Amit Kumar",
                tag: "Lead PhD Candidate",
                desc: "Coordinates computer simulation and predictive process flowsheets. Leads efforts on flux balance analysis.",
                topic: "Systems Modeling & Metabolic Networks of C5/C6 Yeast",
                highlight: "Formulated complete metabolic interface parameters."
            },
            buddhodev: {
                name: "Buddhodev Ghosh",
                tag: "Ph.D. Scholar",
                desc: "Computational bioprocess modeler studying advanced optimization algorithms, intelligence interface systems, and dynamic cell controls.",
                topic: "Intelligent Bioprocess Feedback loop control algorithms",
                highlight: "Engineered scalable PID parameters for anaerobic simulation."
            }
        };

        function selectWebMember(memberId) {
            const selected = webMembersDb[memberId] || webMembersDb.ganti;
            document.getElementById('web-member-name').textContent = selected.name;
            document.getElementById('web-member-tag').textContent = selected.tag;
            document.getElementById('web-member-topic').textContent = selected.topic;
            document.getElementById('web-member-desc').textContent = selected.desc;
            document.getElementById('web-member-highlight').textContent = selected.highlight;
        }

        // --- Place of Memories Helpers ---
        let phpMemories = [];
        try {
            const saved = localStorage.getItem("stlab_memories");
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    phpMemories = parsed;
                }
            }
        } catch(e) {
            // ignore
        }

        const defaultMemories = [
            { id: "7", url: "assets/Thesis_Submission_of_Mr_Abhishek_D_Kalbande.jpeg", year: "2026", fileName: "Thesis_Submission_of_Mr_Abhishek_D_Kalbande", displayName: "STLab's Student, Mr Abhishek D. Kalbande, is submitting his MSc. Thesis", errorNotes: "" },
            { id: "8", url: "assets/Prof_Murthy_with_Foreign_Delegate_and_Prof_Sarkar.jpeg", year: "2026", fileName: "Prof_Murthy_with_Foreign_Delegate_and_Prof_Sarkar", displayName: "Prof. Murthy with Prof. Mark Wilkins, Kansas State Univ. and Prof. Sarkar (PIC, CRDT)", errorNotes: "" },
            { id: "12", url: "assets/Prof._Murthys_Birthday_2025.jpg", year: "2025", fileName: "Prof._Murthys_Birthday_2025", displayName: "STLab team celebrating Prof. Murthy's Birthday", errorNotes: "" },
            { id: "1", url: "assets/Pre-Holi_Celebration_with_Prof_Murthy.jpeg", year: "2026", fileName: "Pre_Holi_Celebration_with_Prof_Murthy", displayName: "STLab researchers enjoying Pre-Holi Celebration", errorNotes: "" },
            { id: "2", url: "assets/Holi_Celebration_with_Prof_Murthy.jpeg", year: "2026", fileName: "Holi_Celebration_with_Prof_Murthy", displayName: "STLab members celebrating Holi festival", errorNotes: "" },
            { id: "3", url: "assets/Chaye_Pe_Charcha.jpeg", year: "2025", fileName: "Chaye_Pe_Charcha", displayName: "STLab researchers discussing core ideas over Chaye Pe Charcha", errorNotes: "" },
            { id: "4", url: "assets/Dinner_with_Prof_Murthy.jpeg", year: "2025", fileName: "Dinner_with_Prof_Murthy", displayName: "STLab team sharing a pleasant dinner", errorNotes: "" },
            { id: "5", url: "assets/Learning_Doesnt_stop_with_Prof_Murthy.jpeg", year: "2025", fileName: "Learning_Doesnt_stop_with_Prof_Murthy", displayName: "STLab scholars engaged in interactive learning", errorNotes: "" },
            { id: "6", url: "assets/Sky_Exploration_with_Prof_Murthy.jpeg", year: "2026", fileName: "Sky_Exploration_with_Prof_Murthy", displayName: "STLab members exploring the telescope during Sky Exploration", errorNotes: "" },
            { id: "9", url: "assets/STL_Labs_Doctoral_Students_with_beloved_Prof.jpeg", year: "2025", fileName: "STL_Labs_Doctoral_Students_with_beloved_Prof", displayName: "STLab's Students with Prof. Murthy (Group Snapshot)", errorNotes: "" },
            { id: "11", url: "assets/Teachers_Day_Celebration_2025.jpeg", year: "2025", fileName: "Teachers_Day_Celebration_2025", displayName: "STLab scholars hosting a surprise Teacher's Day Celebration", errorNotes: "" },
            { id: "10", url: "assets/STL_POD_1B-601.jpeg", year: "2025", fileName: "STL_POD_1B-601", displayName: "STLab team at work inside the state-of-the-art STL POD 1B-601", errorNotes: "" }
        ];

        if (phpMemories.length === 0) {
            phpMemories = defaultMemories;
        } else {
            // Update default memories in the loaded list so changes are forced
            phpMemories = phpMemories.map(item => {
                const def = defaultMemories.find(d => d.id === item.id);
                return def ? Object.assign({}, item, def) : item;
            });
            // If some defaults are missing completely, append them in order
            defaultMemories.forEach(def => {
                if (!phpMemories.some(m => m.id === def.id)) {
                    phpMemories.push(def);
                }
            });
        }
        // Force-persist updated descriptions to localStorage so the user immediately sees changes
        try {
            localStorage.setItem("stlab_memories", JSON.stringify(phpMemories));
        } catch(e) {
            // ignore
        }

        function renderPhpMemories() {
            const countHeader = document.getElementById("php-memories-count-header");
            if (countHeader) {
                countHeader.textContent = "Archived Board Snapshots (" + phpMemories.length + " Memories)";
            }

            const grid = document.getElementById("php-memories-grid");
            if (grid) {
                grid.innerHTML = ""; // Clear existing grid items

                phpMemories.forEach(m => {
                    const yearVal = m.year || (m.date ? (m.date.match(/\d{4}/)?.[0] || "2026") : "2026");
                    const displayNameVal = m.displayName || m.caption || "STLab Memory";
                    const fileNameVal = m.fileName || (m.caption ? m.caption.replace(/\s+/g, '_') : "Uploaded_Image");
                    const errorNotesHtml = m.errorNotes ? `
                        <span class="text-emerald-600 font-bold font-sans text-[9px] flex items-center gap-0.5 mt-0.5 animate-pulse">
                            <i data-lucide="check" class="h-3 w-3 shrink-0 text-emerald-500"></i>
                            ${m.errorNotes}
                        </span>
                    ` : '';

                    const card = document.createElement("div");
                    card.className = "bg-white border border-slate-200 rounded-xl p-3.5 flex flex-col gap-3 relative overflow-hidden shadow-sm group hover:-rotate-1 hover:scale-[1.01] transition-transform duration-300 h-full";
                    card.innerHTML = `
                        <div class="relative aspect-[4/3] w-full rounded-lg overflow-hidden bg-slate-900 shadow-inner flex items-center justify-center">
                            <img src="${m.url}" alt="${displayNameVal}" class="w-full h-full object-cover transition-transform duration-350 group-hover:scale-105" referrerpolicy="no-referrer" />
                            <div class="absolute top-2 right-2 px-2 py-0.5 bg-black/75 backdrop-blur-xs text-[10px] font-bold text-white rounded-md tracking-wider">
                                ${yearVal}
                            </div>
                        </div>
                        <div class="pt-2.5 border-t border-slate-100 flex flex-col gap-1">
                            <div class="font-extrabold text-xs text-slate-800 leading-tight text-left">
                                ${displayNameVal}
                            </div>
                            ${errorNotesHtml}
                        </div>
                    `;
                    grid.appendChild(card);
                });

                // Reinitialize dynamic Lucide icons
                lucide.createIcons();
            }
        }

        window.handlePhpPhotoUpload = function(event) {
            const file = event.target.files?.[0];
            if (file) {
                const reader = new FileReader();
                reader.onloadend = function() {
                    const nameWithoutExt = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                    const dateStr = new Date().toLocaleDateString("en-US", { year: 'numeric', month: 'short', day: 'numeric' });
                    const newMemory = {
                        id: Date.now().toString(),
                        url: reader.result,
                        caption: nameWithoutExt,
                        date: dateStr
                    };
                    phpMemories.unshift(newMemory);
                    localStorage.setItem("stlab_memories", JSON.stringify(phpMemories));
                    renderPhpMemories();
                };
                reader.readAsDataURL(file);
            }
        };

        window.removePhpMemory = function(id) {
            phpMemories = phpMemories.filter(m => m.id !== id);
            localStorage.setItem("stlab_memories", JSON.stringify(phpMemories));
            renderPhpMemories();
        };

        // Trigger memory rendering
        renderPhpMemories();

        // Initialize clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString("en-US", { 
                timeZone: "Asia/Kolkata",
                timeStyle: "medium", 
                hour12: false 
            });
            document.getElementById("live-clock").textContent = timeStr + " IST";
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Publications Tab Filter Function
        window.filterPublications = function() {
            const input = document.getElementById("publication-search-input");
            if (!input) return;
            const query = input.value.toLowerCase().trim();
            const items = document.querySelectorAll(".pub-searchable-item");
            
            items.forEach(item => {
                const text = item.getAttribute("data-search-text") || "";
                if (text.includes(query)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
            
            // Also toggle sections visibility if they contain any matching items
            const sections = ["sec-dissertations", "sec-patents", "sec-journals", "sec-books"];
            sections.forEach(id => {
                const sec = document.getElementById(id);
                if (sec) {
                    const visibleItems = Array.from(sec.querySelectorAll(".pub-searchable-item")).filter(item => item.style.display !== "none");
                    if (visibleItems.length > 0) {
                        sec.style.display = "";
                    } else {
                        sec.style.display = "none";
                    }
                }
            });
        };

        // Run tab initializer on boot
        switchTab(activeTab);
        filterPiTalks('invited');

        // Turn on all icons
        lucide.createIcons();
    </script>
</body>
</html>
