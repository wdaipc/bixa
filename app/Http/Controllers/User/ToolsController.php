<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\FroalaLicenseService;
use App\Services\CDNSpeedTestAdapter;
use App\Services\FontAwesomeHandler;
use App\Services\ServerSideSpeedTestService;
use App\Services\PageSpeedInsightsService;
use App\Services\CombinedSpeedTestService;
use App\Models\Setting;
use App\Models\IperfServer;
use App\Helpers\ServerSelector;
use Illuminate\Mail\Markdown as MailMarkdown;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

class ToolsController extends Controller
{
    protected $froalaLicenseService;
    
    /**
     * Constructor to inject dependencies
     */
    public function __construct(FroalaLicenseService $froalaLicenseService)
    {
        $this->froalaLicenseService = $froalaLicenseService;
    }
    
    /**
     * Get affiliate ID from settings
     *
     * @return string
     */
    private function getAffiliateId()
    {
        return Setting::get('affiliate_id', '12345');
    }
    
    /**
     * Display the Froala License generator page
     *
     * @return \Illuminate\View\View
     */
    public function froalaLicense()
    {
        return view('tools.froala-license');
    }
    
    /**
     * Generate a Froala license key
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function generateFroalaLicense(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'year' => 'required|numeric|min:2025'
        ]);
        
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error' => $validator->errors()->first()
                ], 400);
            }
            
            return back()->withErrors($validator)->withInput();
        }
        
        // Generate license key
        $licenseKey = $this->froalaLicenseService->generateLicense(
            $request->input('name'),
            $request->input('year')
        );
        
        // Return appropriate response based on request type
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'license' => $licenseKey,
                'message' => 'License key generated successfully'
            ]);
        }
        
        return back()->with('license_key', $licenseKey)
                     ->with('success', 'License key generated successfully')
                     ->withInput();
    }
    
    /**
     * Display the case converter tool
     *
     * @return \Illuminate\View\View
     */
    public function caseConverter()
    {
        return view('tools.case-converter');
    }
    
    /**
     * Display the code beautifier tool
     *
     * @return \Illuminate\View\View
     */
    public function codeBeautifier()
    {
        return view('tools.code-beautifier');
    }
    
    /**
     * Display the color tools
     *
     * @return \Illuminate\View\View
     */
    public function colorTools()
    {
        return view('tools.color-tools');
    }
    
    /**
     * Display the base64 encoder/decoder
     *
     * @return \Illuminate\View\View
     */
    public function base64Tool()
    {
        return view('tools.base64');
    }
    
    /**
     * Display the SQL formatter
     * 
     * @return \Illuminate\View\View
     */
    public function sqlFormatter()
    {
        return view('tools.sql-formatter');
    }

    /**
     * Display the CSS Grid Generator tool
     * 
     * @return \Illuminate\View\View
     */
    public function cssGridGenerator()
    {
        return view('tools.css-grid-generator');
    }
    
    /**
     * Display the Hosting Upgrade page
     * 
     * @return \Illuminate\View\View
     */
    public function hostingUpgrade()
    {
        $affiliateId = $this->getAffiliateId();
        return view('tools.hosting-upgrade', compact('affiliateId'));
    }
    
    /**
     * CDN Library Search with improved error handling and Font Awesome support
     */
    public function cdnjsSearch(Request $request)
    {
        $libraryData = null;
        $popularLibraries = [
            ['name' => 'jquery', 'description' => 'jQuery is a fast, small, and feature-rich JavaScript library'],
            ['name' => 'vue', 'description' => 'Progressive JavaScript framework for building user interfaces'],
            ['name' => 'react', 'description' => 'A JavaScript library for building user interfaces'],
            ['name' => 'bootstrap', 'description' => 'The most popular HTML, CSS, and JavaScript framework'],
            ['name' => 'sweetalert2', 'description' => 'A beautiful, responsive, customizable replacement for JavaScript\'s popup boxes'],
            ['name' => 'axios', 'description' => 'Promise based HTTP client for the browser and node.js'],
            ['name' => 'lodash', 'description' => 'A modern JavaScript utility library delivering modularity, performance & extras'],
            ['name' => 'moment', 'description' => 'Parse, validate, manipulate, and display dates and times in JavaScript'],
            ['name' => 'font-awesome', 'description' => 'Font Awesome 4 - The iconic font and CSS toolkit (up to v4.7.0)'],
            ['name' => '@fortawesome/fontawesome-free', 'description' => 'Font Awesome 5+ - The iconic SVG, font, and CSS toolkit (v5.0.0+)']
        ];
        
        $library = $request->input('library');
        $version = $request->input('version');
        $searchQuery = $request->input('query');
        $searchResults = [];
        
        // Search logic with improved handling
        if (!empty($searchQuery)) {
            Log::info("CDN Search Query: {$searchQuery}");
            
            // Special handling for Font Awesome search query
            $isFontAwesomeQuery = stripos($searchQuery, 'font') !== false && stripos($searchQuery, 'awesome') !== false;
            
            if ($isFontAwesomeQuery) {
                try {
                    $fontAwesomeResults = FontAwesomeHandler::searchPackages($searchQuery);
                    if (!empty($fontAwesomeResults)) {
                        $searchResults = $fontAwesomeResults;
                        Log::info("Font Awesome search found results", ['count' => count($fontAwesomeResults)]);
                    }
                } catch (\Exception $e) {
                    Log::error('Font Awesome search error: ' . $e->getMessage());
                }
            }
            
            // Always fallback to regular search if no specific results found
            if (empty($searchResults)) {
                $searchResults = $this->searchLibrariesFromCdnjs($searchQuery);
                Log::info("Regular CDNJS search completed", ['count' => count($searchResults)]);
            }
        }
        
        // Special handling for Font Awesome libraries
        if (!empty($library) && FontAwesomeHandler::isFontAwesome($library)) {
            Log::info("Processing Font Awesome library: $library");
            
            try {
                $libraryData = FontAwesomeHandler::getPackageDetails($library);
                
                // If version is not specified, use the latest version
                if (empty($version) && isset($libraryData['tags']['latest'])) {
                    $version = $libraryData['tags']['latest'];
                } elseif (empty($version) && !empty($libraryData['versions'])) {
                    $version = $libraryData['versions'][0];
                }
                
                // Determine if this version exists on CDNJS
                $existsOnCdnjs = $library !== '@fortawesome/fontawesome-free';
                
                // Get version files
                if (!empty($version)) {
                    $versionFilesData = FontAwesomeHandler::getVersionFiles($library, $version);
                    
                    if (!empty($versionFilesData)) {
                        $libraryData['versionFiles'] = $versionFilesData['files'];
                        $libraryData['source'] = $versionFilesData['source'] ?? ($library === '@fortawesome/fontawesome-free' ? 'jsdelivr' : 'cdnjs');
                        
                        // Generate CDN links using the specialized handler
                        $libraryData['cdnLinks'] = FontAwesomeHandler::generateCdnLinks(
                            $library, 
                            $version, 
                            $versionFilesData['files'], 
                            $existsOnCdnjs
                        );
                        
                        $libraryData['exists_on_cdnjs'] = $existsOnCdnjs;
                    } else {
                        $libraryData['versionFiles'] = [];
                        $libraryData['exists_on_cdnjs'] = false;
                        $libraryData['no_files_found'] = true;
                        $libraryData['source'] = $library === '@fortawesome/fontawesome-free' ? 'jsdelivr' : 'cdnjs';
                    }
                }
                
                // Get README content
                $libraryData['readme'] = $this->getLibraryReadme($library);
                
                // Parse markdown
                if (!empty($libraryData['readme'])) {
                    $libraryData['parsedReadme'] = $this->parseMarkdownWithImageFix($libraryData['readme'], $library, $version);
                }
            } catch (\Exception $e) {
                Log::error("Font Awesome processing error: " . $e->getMessage());
                $libraryData = null;
            }
        }
        // Regular processing for other libraries
        elseif (!empty($library)) {
            try {
                $libraryData = $this->getLibraryDetailsWithVersions($library);
                
                if ($libraryData) {
                    // If version is not specified, use the latest version
                    if (empty($version) && isset($libraryData['tags']['latest'])) {
                        $version = $libraryData['tags']['latest'];
                    } elseif (empty($version) && !empty($libraryData['versions'])) {
                        $version = $libraryData['versions'][0];
                    }
                    
                    // Check if this version exists on CDNJS
                    $existsOnCdnjs = true;
                    if (isset($libraryData['all_versions']) && is_array($libraryData['all_versions'])) {
                        foreach ($libraryData['all_versions'] as $versionData) {
                            if (isset($versionData['version']) && $versionData['version'] === $version) {
                                $existsOnCdnjs = $versionData['exists_on_cdnjs'];
                                break;
                            }
                        }
                    }
                    
                    // Get version files using our helper that tries both sources
                    if (!empty($version)) {
                        $versionFilesData = $this->getLibraryVersionFiles($library, $version);
                        
                        if (!empty($versionFilesData)) {
                            $libraryData['versionFiles'] = $versionFilesData['files'];
                            $libraryData['source'] = $versionFilesData['source'];
                            $existsOnCdnjs = ($versionFilesData['source'] === 'cdnjs');
                            
                            // Generate CDN links for the selected version
                            $libraryData['cdnLinks'] = $this->generateCdnLinks(
                                $library, 
                                $version, 
                                $versionFilesData['files'], 
                                $existsOnCdnjs
                            );
                            $libraryData['exists_on_cdnjs'] = $existsOnCdnjs;
                        } else {
                            $libraryData['versionFiles'] = [];
                            $libraryData['exists_on_cdnjs'] = false;
                            $libraryData['no_files_found'] = true;
                        }
                    }
                    
                    // Get README content
                    $libraryData['readme'] = $this->getLibraryReadme($library);
                    
                    // Pre-parse the markdown to avoid blade issues
                    if (!empty($libraryData['readme'])) {
                        $libraryData['parsedReadme'] = $this->parseMarkdownWithImageFix($libraryData['readme'], $library, $version);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Library processing error for {$library}: " . $e->getMessage());
                $libraryData = null;
            }
        }
        
        return view('tools.cdn-search', compact('libraryData', 'popularLibraries', 'library', 'version', 'searchQuery', 'searchResults'));
    }

    /**
     * Search for libraries on CDNJS with improved error handling
     *
     * @param  string  $query
     * @param  int     $limit
     * @return array
     */
    private function searchLibrariesFromCdnjs($query, $limit = 20)
    {
        $cacheKey = "cdnjs_search_" . md5($query . $limit);
        
        // Try cache first
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult !== null) {
            Log::info("CDNJS search cache hit for: {$query}");
            return $cachedResult;
        }
        
        $url = "https://api.cdnjs.com/libraries";
        $params = [
            'search' => $query,
            'fields' => 'name,description,version,filename,latest,github',
            'limit' => $limit
        ];
        
        $url .= '?' . http_build_query($params);
        
        try {
            Log::info("CDNJS API request: {$url}");
            
            // First try using HTTP facade
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en-US,en;q=0.9'
                ])
                ->get('https://api.cdnjs.com/libraries', $params);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['results']) && is_array($data['results'])) {
                    Log::info("CDNJS search successful via HTTP facade", [
                        'query' => $query,
                        'results_count' => count($data['results'])
                    ]);
                    
                    // Cache successful results for 5 minutes
                    Cache::put($cacheKey, $data['results'], 300);
                    return $data['results'];
                }
            }
            
            // Fallback to file_get_contents if HTTP facade fails
            Log::warning("HTTP facade failed, trying file_get_contents for: {$query}");
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'header' => [
                        'Accept: application/json',
                        'Accept-Language: en-US,en;q=0.9'
                    ]
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                
                if (isset($data['results']) && is_array($data['results'])) {
                    Log::info("CDNJS search successful via file_get_contents", [
                        'query' => $query,
                        'results_count' => count($data['results'])
                    ]);
                    
                    // Cache successful results for 5 minutes
                    Cache::put($cacheKey, $data['results'], 300);
                    return $data['results'];
                }
            }
            
            Log::error("CDNJS API returned invalid data", [
                'query' => $query,
                'url' => $url,
                'response_preview' => substr($response ?: 'null', 0, 200)
            ]);
            
        } catch (\Exception $e) {
            Log::error('CDNJS search error: ' . $e->getMessage(), [
                'query' => $query,
                'url' => $url,
                'exception' => $e->getTraceAsString()
            ]);
        }
        
        // Cache empty results for 1 minute to avoid hammering the API
        Cache::put($cacheKey, [], 60);
        return [];
    }

    /**
     * Get details for a specific library with improved version handling
     * and library name normalization for cross-CDN compatibility
     *
     * @param  string  $library
     * @return array|null
     */
    private function getLibraryDetailsWithVersions($library)
    {
        // Library name mapping for cross-CDN compatibility
        $libraryNameMapping = [
            'font-awesome' => [
                'cdnjs' => 'font-awesome', 
                'jsdelivr' => ['font-awesome', '@fortawesome/fontawesome-free'],
                'unpkg' => ['font-awesome', '@fortawesome/fontawesome-free']
            ],
            '@fortawesome/fontawesome-free' => [
                'cdnjs' => 'font-awesome',
                'jsdelivr' => '@fortawesome/fontawesome-free',
                'unpkg' => '@fortawesome/fontawesome-free'
            ],
            'jquery-ui' => [
                'cdnjs' => 'jqueryui',
                'jsdelivr' => 'jquery-ui',
                'unpkg' => 'jquery-ui'
            ]
        ];
        
        // Normalize library name based on mapping
        $mappedNames = [];
        if (isset($libraryNameMapping[$library])) {
            $mappedNames = $libraryNameMapping[$library];
            Log::info("Using name mapping for library: $library", $mappedNames);
        } else {
            $mappedNames = [
                'cdnjs' => $library,
                'jsdelivr' => $library,
                'unpkg' => $library
            ];
        }
        
        // First get data from CDNJS
        $cdnjsData = $this->getLibraryDetailsFromCdnjs($mappedNames['cdnjs']);
        
        if (!$cdnjsData) {
            // If specific mapping failed, try with original name as fallback
            if ($mappedNames['cdnjs'] !== $library) {
                $cdnjsData = $this->getLibraryDetailsFromCdnjs($library);
            }
            
            // If still no data, try to check if this is an npm-scoped package
            if (!$cdnjsData && strpos($library, '@') === 0 && strpos($library, '/') !== false) {
                $parts = explode('/', $library);
                if (count($parts) > 1) {
                    $simplifiedName = $parts[1];
                    Log::info("Trying simplified name for scoped package: $simplifiedName");
                    $cdnjsData = $this->getLibraryDetailsFromCdnjs($simplifiedName);
                }
            }
            
            if (!$cdnjsData) {
                return null;
            }
        }
        
        // Then get data from jsDelivr, handling multiple possible names
        $jsdelivrData = null;
        if (is_array($mappedNames['jsdelivr'])) {
            foreach ($mappedNames['jsdelivr'] as $name) {
                $jsdelivrData = $this->getLibraryDetailsFromJsdelivr($name);
                if ($jsdelivrData) {
                    Log::info("Found data in jsDelivr using name: $name");
                    break;
                }
            }
        } else {
            $jsdelivrData = $this->getLibraryDetailsFromJsdelivr($mappedNames['jsdelivr']);
        }
        
        // If no jsDelivr data found, try with original name
        if (!$jsdelivrData && $mappedNames['jsdelivr'] !== $library) {
            $jsdelivrData = $this->getLibraryDetailsFromJsdelivr($library);
        }
        
        // Merge version information, prioritizing jsDelivr data
        if ($jsdelivrData && is_array($jsdelivrData) && isset($jsdelivrData['versions']) && is_array($jsdelivrData['versions'])) {
            $cdnjsVersions = [];
            if (isset($cdnjsData) && is_array($cdnjsData) && isset($cdnjsData['versions']) && is_array($cdnjsData['versions'])) {
                $cdnjsVersions = $cdnjsData['versions'];
            }
            
            $cdnjsVersionsLookup = array_flip($cdnjsVersions);
            $mergedVersions = [];
            
            // Extract version strings from jsDelivr data
            $jsdelivrVersions = [];
            foreach ($jsdelivrData['versions'] as $versionObj) {
                if (is_array($versionObj) && isset($versionObj['version']) && is_string($versionObj['version'])) {
                    $jsdelivrVersions[] = $versionObj['version'];
                }
            }
            
            foreach ($jsdelivrVersions as $version) {
                if (isset($cdnjsVersionsLookup[$version])) {
                    $mergedVersions[] = [
                        'version' => $version, 
                        'exists_on_cdnjs' => true
                    ];
                } else {
                    $mergedVersions[] = [
                        'version' => $version, 
                        'exists_on_cdnjs' => false
                    ];
                }
            }
            
            // Sort versions using version_compare for proper semantic versioning
            usort($mergedVersions, function($a, $b) {
                return version_compare($b['version'], $a['version']);
            });
            
            // Update the data with merged and sorted versions
            $cdnjsData['all_versions'] = $mergedVersions;
            $cdnjsData['jsdelivr'] = $jsdelivrData;
            $cdnjsData['original_name'] = $library;
            $cdnjsData['mapped_names'] = $mappedNames;
            
            // Flatten version array for backward compatibility
            $flatVersions = array_map(function($item) {
                return $item['version'];
            }, $mergedVersions);
            
            $cdnjsData['versions'] = $flatVersions;
        }
        
        return $cdnjsData;
    }

    /**
     * Generate CDN links for a library version with minimal Font Awesome fix
     *
     * @param  string  $library
     * @param  string  $version
     * @param  array   $files
     * @param  bool    $existsOnCdnjs
     * @return array
     */
    private function generateCdnLinks($library, $version, $files, $existsOnCdnjs = true)
    {
        if (!is_array($files)) {
            return ['js' => [], 'css' => []];
        }
        
        // Handle Font Awesome special case
        $cdnjsLibrary = $library;
        if ($library === '@fortawesome/fontawesome-free') {
            $cdnjsLibrary = 'font-awesome'; // Use classic name for CDNJS
        }
        
        $jsFiles = array_filter($files, function($file) {
            return is_string($file) && pathinfo($file, PATHINFO_EXTENSION) === 'js';
        });
        
        $cssFiles = array_filter($files, function($file) {
            return is_string($file) && pathinfo($file, PATHINFO_EXTENSION) === 'css';
        });
        
        // Sort to prioritize minified files
        usort($jsFiles, function($a, $b) {
            $aIsMin = strpos($a, '.min.') !== false;
            $bIsMin = strpos($b, '.min.') !== false;
            
            if ($aIsMin && !$bIsMin) return -1;
            if (!$aIsMin && $bIsMin) return 1;
            return 0;
        });
        
        usort($cssFiles, function($a, $b) {
            $aIsMin = strpos($a, '.min.') !== false;
            $bIsMin = strpos($b, '.min.') !== false;
            
            if ($aIsMin && !$bIsMin) return -1;
            if (!$aIsMin && $bIsMin) return 1;
            return 0;
        });
        
        $jsLinks = [];
        $cssLinks = [];
        
        // Determine if it's a GitHub repository
        $isGitHubRepo = strpos($library, '/') !== false && strpos($library, '@') !== 0;
        
        // Prepare file paths
        $commonJsLibraries = ['jquery', 'bootstrap', 'vue', 'react', 'lodash.js', 'moment.js', 'axios'];
        $needsDistPrefix = !in_array($library, $commonJsLibraries) && !$isGitHubRepo;
        
        // Prepare jsdelivr URL base
        $jsdelivrBase = $isGitHubRepo 
            ? "https://cdn.jsdelivr.net/gh/{$library}@{$version}/"
            : "https://cdn.jsdelivr.net/npm/{$library}@{$version}/";
        
        // Prepare unpkg URL base (only for npm packages)
        $unpkgBase = $isGitHubRepo 
            ? null
            : "https://unpkg.com/{$library}@{$version}/";
        
        // Limit to first 3 files of each type
        foreach (array_slice($jsFiles, 0, 3) as $file) {
            $jsdelivrPath = $needsDistPrefix && strpos($file, 'dist/') === false ? 'dist/' . $file : $file;
            $unpkgPath = $needsDistPrefix && strpos($file, 'dist/') === false ? 'dist/' . $file : $file;
            
            $link = [
                'file' => $file,
                'jsdelivr' => [
                    'url' => $jsdelivrBase . $jsdelivrPath,
                    'html' => "<script src=\"{$jsdelivrBase}{$jsdelivrPath}\"></script>"
                ]
            ];
            
            // Add CDNJS links only if the version exists on CDNJS
            if ($existsOnCdnjs) {
                $link['cdnjs'] = [
                    'url' => "https://cdnjs.cloudflare.com/ajax/libs/{$cdnjsLibrary}/{$version}/{$file}",
                    'html' => "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/{$cdnjsLibrary}/{$version}/{$file}\"></script>"
                ];
            }
            
            // Add unpkg links only for npm packages
            if (!$isGitHubRepo) {
                $link['unpkg'] = [
                    'url' => $unpkgBase . $unpkgPath,
                    'html' => "<script src=\"{$unpkgBase}{$unpkgPath}\"></script>"
                ];
            }
            
            $jsLinks[] = $link;
        }
        
        foreach (array_slice($cssFiles, 0, 3) as $file) {
            $jsdelivrPath = $needsDistPrefix && strpos($file, 'dist/') === false ? 'dist/' . $file : $file;
            $unpkgPath = $needsDistPrefix && strpos($file, 'dist/') === false ? 'dist/' . $file : $file;
            
            $link = [
                'file' => $file,
                'jsdelivr' => [
                    'url' => $jsdelivrBase . $jsdelivrPath,
                    'html' => "<link rel=\"stylesheet\" href=\"{$jsdelivrBase}{$jsdelivrPath}\">"
                ]
            ];
            
            // Add CDNJS links only if the version exists on CDNJS
            if ($existsOnCdnjs) {
                $link['cdnjs'] = [
                    'url' => "https://cdnjs.cloudflare.com/ajax/libs/{$cdnjsLibrary}/{$version}/{$file}",
                    'html' => "<link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/{$cdnjsLibrary}/{$version}/{$file}\">"
                ];
            }
            
            // Add unpkg links only for npm packages
            if (!$isGitHubRepo) {
                $link['unpkg'] = [
                    'url' => $unpkgBase . $unpkgPath,
                    'html' => "<link rel=\"stylesheet\" href=\"{$unpkgBase}{$unpkgPath}\">"
                ];
            }
            
            $cssLinks[] = $link;
        }
        
        return [
            'js' => $jsLinks,
            'css' => $cssLinks
        ];
    }

    /**
     * Get details for a specific library on CDNJS
     *
     * @param  string  $library
     * @return array|null
     */
    private function getLibraryDetailsFromCdnjs($library)
    {
        $url = "https://api.cdnjs.com/libraries/{$library}?fields=name,description,version,author,homepage,license,filename,keywords,assets,tags,versions,github";
        
        try {
            // Try HTTP facade first
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'Accept' => 'application/json'
                ])
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)'
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            
            if ($response !== false) {
                $data = json_decode($response, true);
                if ($data) {
                    return $data;
                }
            }
            
        } catch (\Exception $e) {
            Log::error("CDNJS library details error for {$library}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get library details from jsDelivr API
     *
     * @param  string  $library
     * @return array|null
     */
    private function getLibraryDetailsFromJsdelivr($library)
    {
        try {
            // Determine if it's a GitHub repository
            $isGitHubRepo = strpos($library, '/') !== false;
            
            if ($isGitHubRepo) {
                list($owner, $repo) = explode('/', $library);
                $jsdelivrUrl = "https://data.jsdelivr.com/v1/packages/gh/{$owner}/{$repo}";
            } else {
                $jsdelivrUrl = "https://data.jsdelivr.com/v1/packages/npm/{$library}";
            }
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'Accept' => 'application/json'
                ])
                ->get($jsdelivrUrl);
            
            if ($response->successful()) {
                return $response->json();
            }
            
        } catch (\Exception $e) {
            Log::error("jsDelivr library details error for {$library}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get version details for a specific library on CDNJS
     *
     * @param  string  $library
     * @param  string  $version
     * @return array|null
     */
    private function getLibraryVersionFromCdnjs($library, $version)
    {
        $url = "https://api.cdnjs.com/libraries/{$library}/{$version}";
        
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'Accept' => 'application/json'
                ])
                ->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
        } catch (\Exception $e) {
            Log::error("CDNJS version details error for {$library}@{$version}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Get version details for a specific library from jsDelivr API
     *
     * @param  string  $library
     * @param  string  $version
     * @return array|null
     */
    private function getLibraryVersionFromJsdelivr($library, $version)
    {
        try {
            // Determine if it's a GitHub repository
            $isGitHubRepo = strpos($library, '/') !== false;
            
            if ($isGitHubRepo) {
                list($owner, $repo) = explode('/', $library);
                $jsdelivrUrl = "https://data.jsdelivr.com/v1/packages/gh/{$owner}/{$repo}@{$version}";
            } else {
                $jsdelivrUrl = "https://data.jsdelivr.com/v1/packages/npm/{$library}@{$version}";
            }
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'Accept' => 'application/json'
                ])
                ->get($jsdelivrUrl);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (!$data || !is_array($data) || !isset($data['files']) || !is_array($data['files'])) {
                    return null;
                }
                
                // Convert jsDelivr format to CDNJS-compatible format
                $files = [];
                $this->extractFilesFromJsdelivrData($data['files'], $files);
                
                return [
                    'version' => $version,
                    'files' => $files
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("jsDelivr version details error for {$library}@{$version}: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Extract files from jsDelivr response data
     *
     * @param  array  $filesData
     * @param  array  &$result
     * @param  string $prefix
     * @return void
     */
    private function extractFilesFromJsdelivrData($filesData, &$result, $prefix = '')
    {
        if (!is_array($filesData)) {
            return;
        }
        
        foreach ($filesData as $item) {
            if (!is_array($item) || !isset($item['type'])) {
                continue;
            }
            
            if ($item['type'] === 'directory' && isset($item['files']) && is_array($item['files'])) {
                $this->extractFilesFromJsdelivrData(
                    $item['files'], 
                    $result, 
                    $prefix . $item['name'] . '/'
                );
            } else if ($item['type'] === 'file' && isset($item['name'])) {
                $result[] = $prefix . $item['name'];
            }
        }
    }

    /**
     * Get library version files, trying CDNJS first then falling back to jsDelivr
     *
     * @param  string  $library
     * @param  string  $version
     * @return array|null
     */
    private function getLibraryVersionFiles($library, $version)
    {
        // Try CDNJS first
        $cdnjsVersionData = $this->getLibraryVersionFromCdnjs($library, $version);
        
        if (!empty($cdnjsVersionData) && is_array($cdnjsVersionData) && isset($cdnjsVersionData['files']) && is_array($cdnjsVersionData['files'])) {
            return [
                'files' => $cdnjsVersionData['files'],
                'source' => 'cdnjs'
            ];
        }
        
        // Fall back to jsDelivr if CDNJS doesn't have this version
        $jsdelivrVersionData = $this->getLibraryVersionFromJsdelivr($library, $version);
        
        if (!empty($jsdelivrVersionData) && is_array($jsdelivrVersionData) && isset($jsdelivrVersionData['files']) && is_array($jsdelivrVersionData['files'])) {
            return [
                'files' => $jsdelivrVersionData['files'],
                'source' => 'jsdelivr'
            ];
        }
        
        return null;
    }

    /**
     * Get README content for a library
     *
     * @param  string  $library
     * @return string|null
     */
    private function getLibraryReadme($library)
    {
        try {
            // Try different README filenames
            $readmeFiles = ['README.md', 'readme.md', 'Readme.md'];
            
            // Determine if it's a GitHub repository
            $isGitHubRepo = strpos($library, '/') !== false;
            
            if ($isGitHubRepo) {
                // For GitHub repositories
                list($owner, $repo) = explode('/', $library);
                
                foreach ($readmeFiles as $file) {
                    $url = "https://cdn.jsdelivr.net/gh/{$owner}/{$repo}@latest/{$file}";
                    $response = @file_get_contents($url);
                    
                    if ($response !== false) {
                        return $response;
                    }
                }
            } else {
                // For npm packages
                foreach ($readmeFiles as $file) {
                    $url = "https://cdn.jsdelivr.net/npm/{$library}/{$file}";
                    $response = @file_get_contents($url);
                    
                    if ($response !== false) {
                        return $response;
                    }
                }
            }
            
            // If no README found, try GitHub API as a fallback
            if ($isGitHubRepo) {
                $githubApiUrl = "https://api.github.com/repos/{$owner}/{$repo}/readme";
                $response = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)'])
                    ->get($githubApiUrl);
                
                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['content'])) {
                        return base64_decode($data['content']);
                    }
                }
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("README fetch error for {$library}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse markdown content and fix relative image paths for GitHub repositories
     *
     * @param string $content
     * @param string $library
     * @param string $version
     * @return string
     */
    private function parseMarkdownWithImageFix($content, $library = null, $version = null)
    {
        try {
            // First convert markdown to HTML using Laravel's built-in Markdown parser
            $html = MailMarkdown::parse($content)->toHtml();
            
            // If library and version are provided, fix relative image paths
            if ($library && $version) {
                // Load HTML into DOMDocument with proper UTF-8 handling
                $dom = new DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                
                // Find all image tags
                $xpath = new DOMXPath($dom);
                $images = $xpath->query('//img');
                
                // Is this a GitHub repository format?
                $isGitHubRepo = strpos($library, '/') !== false;
                
                // Get GitHub owner/repo if applicable
                $owner = $repo = null;
                if ($isGitHubRepo) {
                    list($owner, $repo) = explode('/', $library);
                }
                
                foreach ($images as $img) {
                    $src = $img->getAttribute('src');
                    
                    // Only process relative URLs
                    if (!empty($src) && !preg_match('/^(https?:\/\/|\/\/|data:)/', $src)) {
                        // Remove leading ./ or ../
                        $cleanSrc = preg_replace('/^\.\/|^\.\.\//', '', $src);
                        
                        // Create CDN URL based on library type
                        if ($isGitHubRepo) {
                            $cdnUrl = "https://cdn.jsdelivr.net/gh/{$owner}/{$repo}@latest/{$cleanSrc}";
                            
                            // Special case for sweetalert2
                            if ($library === 'sweetalert2/sweetalert2' && strpos($cleanSrc, 'swal2-logo.png') !== false) {
                                $cdnUrl = "https://cdn.jsdelivr.net/gh/sweetalert2/sweetalert2@839d906cabda403cf5e647b6ff1008198d2455f9/assets/swal2-logo.png";
                            }
                        } else {
                            $cdnUrl = "https://cdn.jsdelivr.net/npm/{$library}@{$version}/{$cleanSrc}";
                            
                            // Special case for sweetalert2
                            if ($library === 'sweetalert2' && strpos($cleanSrc, 'swal2-logo.png') !== false) {
                                $cdnUrl = "https://cdn.jsdelivr.net/gh/sweetalert2/sweetalert2@839d906cabda403cf5e647b6ff1008198d2455f9/assets/swal2-logo.png";
                            }
                        }
                        
                        $img->setAttribute('src', $cdnUrl);
                        $img->setAttribute('data-library', $library);
                        $img->setAttribute('data-version', $version);
                        $img->setAttribute('data-original-src', $cleanSrc);
                        $img->setAttribute('onerror', "this.onerror=null; this.src='https://via.placeholder.com/300x150?text=Image+Not+Found';");
                    }
                }
                
                // Get the modified HTML
                $html = $dom->saveHTML();
            }
            
            return $html;
            
        } catch (\Exception $e) {
            // In case parsing fails, return the original content with plain formatting
            return '<div style="white-space: pre-wrap; font-family: monospace;">' . htmlspecialchars($content) . '</div>';
        }
    }

    /**
     * Handle AJAX search requests for CDNJS libraries with improved logic
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cdnjsSearchAjax(Request $request)
    {
        $searchQuery = $request->input('query');
        $searchResults = [];
        
        if (!empty($searchQuery)) {
            Log::info("AJAX search query: {$searchQuery}");
            
            // Special handling for Font Awesome search
            $isFontAwesomeQuery = stripos($searchQuery, 'font') !== false && stripos($searchQuery, 'awesome') !== false;
            
            if ($isFontAwesomeQuery) {
                try {
                    $fontAwesomeResults = FontAwesomeHandler::searchPackages($searchQuery);
                    
                    if (!empty($fontAwesomeResults)) {
                        Log::info("Font Awesome AJAX search found results", ['count' => count($fontAwesomeResults)]);
                        return response()->json([
                            'results' => $fontAwesomeResults
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Font Awesome AJAX search error: ' . $e->getMessage());
                }
            }
            
            // Regular search for all cases (including Font Awesome fallback)
            $searchResults = $this->searchLibrariesFromCdnjs($searchQuery);
            Log::info("Regular AJAX search completed", ['count' => count($searchResults)]);
        }
        
        return response()->json([
            'results' => $searchResults
        ]);
    }

    /**
     * Debug CDNJS API connectivity
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function debugCdnjsApi(Request $request)
    {
        $query = $request->input('query', 'jquery');
        
        try {
            // Test direct API call
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; CDNSearchBot/1.0)',
                    'Accept' => 'application/json'
                ])
                ->get('https://api.cdnjs.com/libraries', [
                    'search' => $query,
                    'fields' => 'name,description',
                    'limit' => 5
                ]);
                
            return response()->json([
                'query' => $query,
                'status' => $response->status(),
                'successful' => $response->successful(),
                'headers' => $response->headers(),
                'body' => $response->json(),
                'url' => $response->effectiveUri()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'query' => $query
            ], 500);
        }
    }

    /**
     * Handle server-side website speed test
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function serverSideSpeedTest(Request $request)
    {
        // Increase execution time limit
        set_time_limit(180);
        
        try {
            // Validate URL
            $validator = Validator::make($request->all(), [
                'url' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $url = $request->input('url');
            
            // Get user IP and location info
            $userIp = $request->ip();
            $userLocationInfo = $this->getUserLocationInfo($userIp);
            
            // Initialize performance tracking
            $startTime = microtime(true);
            Log::info("Starting server-side speed test for URL: $url");
            
            // Run the server-side speed test
            /** @var ServerSideSpeedTestService $speedTestService */
            $speedTestService = app(ServerSideSpeedTestService::class);
            $testResults = $speedTestService->runComprehensiveTest($url);
            
            // Track execution time
            $executionTime = round((microtime(true) - $startTime) * 1000);
            Log::info("Server-side speed test completed in {$executionTime}ms for URL: $url");
            
            // Add user location info to response
            $testResults['user_location'] = [
                'ip' => $userIp,
                'location' => $userLocationInfo
            ];
            
            // Add execution metadata
            $testResults['execution_time'] = $executionTime;
            $testResults['server_timestamp'] = now()->toIso8601String();
            
            // Return the results with appropriate headers
            return response()->json([
                'success' => true,
                'test_results' => $testResults
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            // Log detailed error
            Log::error('Server-side speed test error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Return error response with specific message
            return response()->json([
                'success' => false,
                'message' => 'Error running speed test: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }
    
    /**
     * Handle PageSpeed Insights API test
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function pageSpeedTest(Request $request)
    {
        // Increase time limit
        set_time_limit(180);
        
        try {
            // Validate URL
            $validator = Validator::make($request->all(), [
                'url' => 'required|string',
                'strategy' => 'required|in:mobile,desktop'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $url = $request->input('url');
            $strategy = $request->input('strategy', 'mobile');
            
            // Check if PageSpeed Insights is enabled
            $isEnabled = \App\Models\Setting::get('enable_pagespeed', '0') === '1';
            if (!$isEnabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'PageSpeed Insights is disabled in settings.'
                ], 400);
            }
            
            // Get API key
            $apiKey = \App\Models\Setting::get('pagespeed_api_key', null);
            if (empty($apiKey)) {
                \Log::warning('PageSpeed Insights API key not configured');
            }
            
            // Run the PageSpeed Insights test
            /** @var \App\Services\PageSpeedInsightsService $pageSpeedService */
            $pageSpeedService = app(\App\Services\PageSpeedInsightsService::class);
            
            // Increase timeout to 2 minutes
            $pageSpeedService->setTimeout(120);
            
            // Logging
            \Log::info("Starting PageSpeed test for URL: $url with strategy: $strategy");
            
            // Measure execution time
            $startTime = microtime(true);
            
            $results = $pageSpeedService->runPageSpeedTest($url, $strategy);
            
            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime), 2);
            \Log::info("PageSpeed test completed in {$executionTime} seconds");
            
            if (!$results) {
                // Return a response with has_error=true but success=true
                return response()->json([
                    'success' => true,
                    'has_error' => true,
                    'message' => "PageSpeed Insights could not analyze this URL after {$executionTime} seconds. The website might be too large or the API is experiencing issues."
                ]);
            }
            
            // Extract key metrics for simpler response
            $metrics = $pageSpeedService->extractKeyMetrics($results);
            
            // Debug log
            \Log::debug('Extracted PageSpeed metrics:', $metrics ?: ['No metrics extracted']);
            
            // Format metrics to ensure all necessary keys exist with consistent naming
            $formattedMetrics = $this->formatMetricsForFrontend($metrics);
            
            // Debug log
            \Log::debug('Formatted PageSpeed scores:', $formattedMetrics['scores'] ?? []);
            
            // Return the results
            return response()->json([
                'success' => true,
                'has_error' => false,
                'metrics' => $formattedMetrics,
                'execution_time' => $executionTime,
                'full_results' => $results
            ]);
        } catch (\Exception $e) {
            \Log::error('PageSpeed test error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            $errorMessage = $e->getMessage();
            $friendlyMessage = 'Error running PageSpeed test';
            
            if (strpos($errorMessage, 'timed out') !== false) {
                $friendlyMessage = 'Request timed out while analyzing the website. The site might be too large or complex.';
            } elseif (strpos($errorMessage, 'cURL error 28') !== false) {
                $friendlyMessage = 'The website took too long to analyze. Please try a different URL or try again later.';
            }
            
            return response()->json([
                'success' => true, // Still returning success=true so frontend can display friendly message
                'has_error' => true,
                'message' => $friendlyMessage
            ]);
        }
    }

    /**
     * Extract key metrics from PageSpeed results with improved field handling
     *
     * @param array $pageSpeedResults Full PageSpeed results
     * @return array Key metrics in a simplified format
     */
    public function extractKeyMetrics($pageSpeedResults)
    {
        if (!$pageSpeedResults || !isset($pageSpeedResults['lighthouseResult'])) {
            Log::error('PageSpeed: Missing lighthouseResult in response');
            return null;
        }
        
        $lighthouse = $pageSpeedResults['lighthouseResult'];
        
        // Debug log to see the structure
        Log::debug('PageSpeed lighthouse result structure:', array_keys($lighthouse));
        
        // Kiểm tra cấu trúc dữ liệu
        if (!isset($lighthouse['categories'])) {
            Log::error('PageSpeed: Missing categories in lighthouseResult');
            return null;
        }
        
        // Get scores - với logging chi tiết
        $scores = [
            'performance' => isset($lighthouse['categories']['performance']['score']) 
                ? round($lighthouse['categories']['performance']['score'] * 100) 
                : null,
            'accessibility' => isset($lighthouse['categories']['accessibility']['score']) 
                ? round($lighthouse['categories']['accessibility']['score'] * 100) 
                : null,
            'best_practices' => isset($lighthouse['categories']['best-practices']['score']) 
                ? round($lighthouse['categories']['best-practices']['score'] * 100) 
                : null,
            'seo' => isset($lighthouse['categories']['seo']['score']) 
                ? round($lighthouse['categories']['seo']['score'] * 100) 
                : null
        ];
        
        // Add alternative field name for better compatibility
        if (isset($lighthouse['categories']['best-practices']['score'])) {
            $scores['bestPractices'] = round($lighthouse['categories']['best-practices']['score'] * 100);
        }
        
        // Log để debug
        Log::debug('PageSpeed scores extracted:', $scores);
        
        // Get key metrics from audits - kiểm tra kỹ lưỡng
        $metrics = [];
        
        if (isset($lighthouse['audits'])) {
            $audits = $lighthouse['audits'];
            
            // First Contentful Paint
            if (isset($audits['first-contentful-paint'])) {
                $metrics['first_contentful_paint'] = [
                    'title' => 'First Contentful Paint',
                    'value' => $audits['first-contentful-paint']['displayValue'],
                    'score' => $audits['first-contentful-paint']['score'],
                    'description' => $audits['first-contentful-paint']['description'] ?? null
                ];
            }
            
            // Largest Contentful Paint
            if (isset($audits['largest-contentful-paint'])) {
                $metrics['largest_contentful_paint'] = [
                    'title' => 'Largest Contentful Paint',
                    'value' => $audits['largest-contentful-paint']['displayValue'],
                    'score' => $audits['largest-contentful-paint']['score'],
                    'description' => $audits['largest-contentful-paint']['description'] ?? null
                ];
            }
            
            // Speed Index
            if (isset($audits['speed-index'])) {
                $metrics['speed_index'] = [
                    'title' => 'Speed Index',
                    'value' => $audits['speed-index']['displayValue'],
                    'score' => $audits['speed-index']['score'],
                    'description' => $audits['speed-index']['description'] ?? null
                ];
            }
            
            // Time to Interactive
            if (isset($audits['interactive'])) {
                $metrics['time_to_interactive'] = [
                    'title' => 'Time to Interactive',
                    'value' => $audits['interactive']['displayValue'],
                    'score' => $audits['interactive']['score'],
                    'description' => $audits['interactive']['description'] ?? null
                ];
            }
            
            // Total Blocking Time
            if (isset($audits['total-blocking-time'])) {
                $metrics['total_blocking_time'] = [
                    'title' => 'Total Blocking Time',
                    'value' => $audits['total-blocking-time']['displayValue'],
                    'score' => $audits['total-blocking-time']['score'],
                    'description' => $audits['total-blocking-time']['description'] ?? null
                ];
            }
            
            // Cumulative Layout Shift
            if (isset($audits['cumulative-layout-shift'])) {
                $metrics['cumulative_layout_shift'] = [
                    'title' => 'Cumulative Layout Shift',
                    'value' => $audits['cumulative-layout-shift']['displayValue'],
                    'score' => $audits['cumulative-layout-shift']['score'],
                    'description' => $audits['cumulative-layout-shift']['description'] ?? null
                ];
            }
        }
        
        // Get lab data
        $labData = isset($lighthouse['audits']['metrics']['details']['items'][0]) 
            ? $lighthouse['audits']['metrics']['details']['items'][0] 
            : null;
        
        // Get opportunities and diagnostics
        $opportunities = [];
        $diagnostics = [];
        
        if (isset($lighthouse['categories']['performance']['auditRefs'])) {
            $performanceAudits = $lighthouse['categories']['performance']['auditRefs'];
            
            foreach ($performanceAudits as $auditRef) {
                $id = $auditRef['id'];
                
                // Skip if not an opportunity or diagnostic
                if (!isset($auditRef['group']) || !in_array($auditRef['group'], ['load-opportunities', 'diagnostics'])) {
                    continue;
                }
                
                if (!isset($lighthouse['audits'][$id]) || $lighthouse['audits'][$id]['score'] === 1) {
                    continue;
                }
                
                $audit = $lighthouse['audits'][$id];
                $auditData = [
                    'id' => $id,
                    'title' => $audit['title'],
                    'description' => $audit['description'],
                    'score' => $audit['score'],
                    'display_value' => $audit['displayValue'] ?? null
                ];
                
                if ($auditRef['group'] === 'load-opportunities') {
                    $opportunities[] = $auditData;
                } elseif ($auditRef['group'] === 'diagnostics') {
                    $diagnostics[] = $auditData;
                }
            }
        }
        
        // Construct final result
        return [
            'scores' => $scores,
            'metrics' => $metrics,
            'lab_data' => $labData,
            'opportunities' => $opportunities,
            'diagnostics' => $diagnostics,
            'strategy' => $lighthouse['configSettings']['emulatedFormFactor'],
            'fetch_time' => $lighthouse['fetchTime'],
            'url' => $lighthouse['finalUrl']
        ];
    }

    /**
     * Format metrics to ensure consistent key naming for frontend
     * Resolves issues with inconsistent key naming (underscores vs hyphens)
     *
     * @param array|null $metrics Original metrics from PageSpeed
     * @return array Formatted metrics with consistent keys
     */
    private function formatMetricsForFrontend($metrics)
    {
        if (!$metrics || !isset($metrics['scores'])) {
            return $metrics ?? [];
        }
        
        // Create a copy to avoid modifying the original
        $formattedMetrics = $metrics;
        $scores = &$formattedMetrics['scores'];
        
        // Ensure all naming variations are included for best practices score
        if (isset($scores['best_practices'])) {
            $scores['best-practices'] = $scores['best_practices'];
            $scores['bestPractices'] = $scores['best_practices'];
        } elseif (isset($scores['best-practices'])) {
            $scores['best_practices'] = $scores['best-practices']; 
            $scores['bestPractices'] = $scores['best-practices'];
        } elseif (isset($scores['bestPractices'])) {
            $scores['best_practices'] = $scores['bestPractices'];
            $scores['best-practices'] = $scores['bestPractices'];
        }
        
        // Ensure all scores have values (even if null) to avoid undefined errors
        $requiredScores = ['performance', 'accessibility', 'best_practices', 'best-practices', 'bestPractices', 'seo'];
        foreach ($requiredScores as $scoreKey) {
            if (!isset($scores[$scoreKey])) {
                $scores[$scoreKey] = null;
            }
        }
        
        // Log the final scores to help with debugging
        Log::debug('Formatted PageSpeed scores:', $scores);
        
        return $formattedMetrics;
    }

    /**
     * Get user location information from IP address
     * 
     * @param string $ip
     * @return array
     */
    private function getUserLocationInfo($ip)
    {
        try {
            // Prioritize local GeoIP database if available
            if (function_exists('geoip_record_by_name')) {
                $record = @geoip_record_by_name($ip);
                
                if ($record) {
                    return [
                        'country' => $record['country_name'] ?? 'Unknown',
                        'country_code' => $record['country_code'] ?? 'XX',
                        'city' => $record['city'] ?? 'Unknown',
                        'region' => $record['region'] ?? 'Unknown',
                        'latitude' => $record['latitude'] ?? 0,
                        'longitude' => $record['longitude'] ?? 0
                    ];
                }
            }
            
            // Fallback to IP-API free service
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,lat,lon");
            
            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();
                
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'country_code' => $data['countryCode'] ?? 'XX',
                    'city' => $data['city'] ?? 'Unknown',
                    'region' => $data['regionName'] ?? 'Unknown',
                    'latitude' => $data['lat'] ?? 0,
                    'longitude' => $data['lon'] ?? 0
                ];
            }
            
            return [
                'country' => 'Unknown',
                'country_code' => 'XX',
                'city' => 'Unknown',
                'region' => 'Unknown',
                'latitude' => 0,
                'longitude' => 0
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user location info: ' . $e->getMessage());
            
            return [
                'country' => 'Unknown',
                'country_code' => 'XX',
                'city' => 'Unknown',
                'region' => 'Unknown',
                'latitude' => 0,
                'longitude' => 0
            ];
        }
    }

    /**
     * Handle CheckHost testing
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkHostTest(Request $request)
    {
        // Increase time limit
        set_time_limit(180);
        
        try {
            // Validate URL
            $validator = Validator::make($request->all(), [
                'url' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $url = $request->input('url');
            
            // Get user IP and location info
            $userIp = $request->ip();
            $userLocationInfo = $this->getUserLocationInfo($userIp);
            
            // Initialize performance tracking
            $startTime = microtime(true);
            Log::info("Starting CheckHost test for URL: $url");
            
            // Initialize API and run test
            $checkHostApi = new \App\Libraries\CheckHostApi();
            $testResults = $checkHostApi->runComprehensiveTest($url);
            
            // Track execution time
            $executionTime = round((microtime(true) - $startTime) * 1000);
            Log::info("CheckHost test completed in {$executionTime}ms for URL: $url");
            
            // Add user location info to response
            $testResults['user_location'] = [
                'ip' => $userIp,
                'location' => $userLocationInfo
            ];
            
            // Add execution metadata
            $testResults['execution_time'] = $executionTime;
            $testResults['server_timestamp'] = now()->toIso8601String();
            
            // Return results with appropriate headers
            return response()->json([
                'success' => true,
                'test_results' => $testResults
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            // Log detailed error
            Log::error('CheckHost test error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Return specific error message
            return response()->json([
                'success' => false,
                'message' => 'Error testing website: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Handle combined website speed test
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function combinedSpeedTest(Request $request)
    {
        // Increase time limit and memory limit
        set_time_limit(180);
        ini_set('memory_limit', '256M');
        
        try {
            // Validate URL
            $validator = Validator::make($request->all(), [
                'url' => 'required|string',
                'strategy' => 'nullable|in:mobile,desktop',
                'use_check_host' => 'nullable|boolean',
                'use_all_nodes' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $url = $request->input('url');
            $strategy = $request->input('strategy', 'desktop');
            $useCheckHost = $request->input('use_check_host', true);
            $useAllNodes = $request->input('use_all_nodes', false);
            
            // Before starting full test, check if domain is responsive
            $preCheckResult = $this->preCheckDomain($request);
            $preCheckData = json_decode($preCheckResult->getContent(), true);
            
            if (!isset($preCheckData['success']) || !$preCheckData['success']) {
                return $preCheckResult;
            }
            
            // Get user IP and location
            $userIp = $request->ip();
            $userLocationInfo = $this->getUserLocationInfo($userIp);
            
            // Start performance tracking
            $startTime = microtime(true);
            Log::info("Starting combined speed test for URL: $url");
            
            // Run the combined speed test
            /** @var \App\Services\CombinedSpeedTestService $combinedService */
            $combinedService = app(\App\Services\CombinedSpeedTestService::class);
            
            // Set options for the test
            $options = [
                'use_check_host' => $useCheckHost,
                'use_all_nodes' => $useAllNodes,
                '_cache_buster' => $request->input('_cache_buster', null)
            ];
            
            $testResults = $combinedService->runCombinedTest($url, $strategy, $options);
            
            // Track execution time
            $executionTime = round((microtime(true) - $startTime) * 1000);
            Log::info("Combined speed test completed in {$executionTime}ms for URL: $url");
            
            // Add user location info to response
            $testResults['user_location'] = [
                'ip' => $userIp,
                'location' => $userLocationInfo
            ];
            
            // Add execution metadata
            $testResults['execution_time'] = $executionTime;
            $testResults['server_timestamp'] = now()->toIso8601String();
            
            // Return the results with appropriate headers
            return response()->json([
                'success' => true,
                'test_results' => $testResults
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            // Log detailed error
            Log::error('Combined speed test error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Return error response with specific message
            return response()->json([
                'success' => false,
                'message' => 'Error running speed test: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Pre-check domain accessibility before running full tests
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preCheckDomain(Request $request)
    {
        try {
            // Validate URL
            $validator = Validator::make($request->all(), [
                'url' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $url = $request->input('url');
            
            // Normalize URL (ensure it has http/https)
            if (!preg_match('/^https?:\/\//i', $url)) {
                $url = 'https://' . $url;
            }
            
            // Extract domain from URL
            $parsedUrl = parse_url($url);
            $domain = $parsedUrl['host'] ?? '';
            
            // Check if domain is even valid
            if (empty($domain) || !preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid domain name format'
                ], 400);
            }
            
            // Check DNS records first (fastest check)
            if (!checkdnsrr($domain, 'A') && !checkdnsrr($domain, 'AAAA')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain DNS records not found. The domain may not exist or may not be properly configured.'
                ], 404);
            }
            
            // Attempt a lightweight HTTP request to check if the server responds
            $response = Http::timeout(5)
                ->withOptions([
                    'verify' => false,
                    'allow_redirects' => false
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; PreCheckBot/1.0)',
                ])
                ->head("https://{$domain}");
            
            // Try HTTPS first, then fallback to HTTP if needed
            $protocol = 'https';
            $errorMessage = null;
            
            if (!$response->successful()) {
                // HTTPS failed, try HTTP
                $protocol = 'http';
                
                $response = Http::timeout(5)
                    ->withOptions([
                        'verify' => false,
                        'allow_redirects' => false
                    ])
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (compatible; PreCheckBot/1.0)',
                    ])
                    ->head("http://{$domain}");
                
                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Domain appears to be unreachable. Please verify the domain is operational.',
                        'status_code' => $response->status()
                    ], 404);
                }
            }
            
            // Any response below 500 is considered "reachable"
            $statusCode = $response->status();
            if ($statusCode < 500) {
                return response()->json([
                    'success' => true,
                    'message' => 'Domain is reachable',
                    'protocol' => $protocol,
                    'status_code' => $statusCode
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain returned a server error (HTTP ' . $statusCode . ')',
                    'status_code' => $statusCode
                ], 400);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking domain: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export speed test results as HTML
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportSpeedTestResults(Request $request)
    {
        try {
            // Validate input
            $validator = Validator::make($request->all(), [
                'results' => 'required|json',
                'url' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            // Decode the JSON results
            $testResults = json_decode($request->input('results'), true);
            $url = $request->input('url');
            
            // If results are invalid, return error
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($testResults)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid test results format'
                ], 400);
            }
            
            // Generate HTML report
            $html = view('tools.speed-test-export', [
                'testResults' => $testResults,
                'url' => $url,
                'generatedAt' => now()->format('Y-m-d H:i:s')
            ])->render();
            
            // Return HTML as response with appropriate headers
            return response($html)
                ->header('Content-Type', 'text/html')
                ->header('Content-Disposition', 'attachment; filename="website_speed_test_' . str_replace(['http://', 'https://', '/'], '', $url) . '_' . date('Y-m-d') . '.html"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating export: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CDN edge servers for speed testing
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCdnEdgeServers(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'library' => 'required|string',
                'version' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $library = $request->input('library');
            $version = $request->input('version');
            
            // Get user IP and location info
            $userIp = $request->ip();
            $userLocationInfo = $this->getUserLocationInfo($userIp);
            
            // Initialize the CDN Speed Test Adapter
            $cdnSpeedTestAdapter = app(CDNSpeedTestAdapter::class);
            
            // Get CDN providers and their edge servers
            $cdnProviders = $cdnSpeedTestAdapter->getCDNProviders($library, $version);
            
            // Return the results with appropriate headers
            return response()->json([
                'success' => true,
                'user_location' => [
                    'ip' => $userIp,
                    'location' => $userLocationInfo
                ],
                'cdn_providers' => $cdnProviders,
                'server_time' => now()->toIso8601String()
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            // Log detailed error
            Log::error('Error fetching CDN edge servers: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'Error fetching CDN edge servers: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Run CDN speed test from server side
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function runCdnSpeedTest(Request $request)
    {
        // Increase execution time limit
        set_time_limit(180);
        
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'library' => 'required|string',
                'version' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $library = $request->input('library');
            $version = $request->input('version');
            
            // Get user IP and location info
            $userIp = $request->ip();
            $userLocationInfo = $this->getUserLocationInfo($userIp);
            
            // Initialize performance tracking
            $startTime = microtime(true);
            Log::info("Starting CDN speed test for library: $library, version: $version");
            
            // Run the CDN speed test
            $cdnSpeedTestAdapter = app(CDNSpeedTestAdapter::class);
            $testResults = $cdnSpeedTestAdapter->testCDNSpeed($library, $version);
            
            // Ensure results is an array, not an associative array
            if (!isset($testResults['results']) || !is_array($testResults['results'])) {
                Log::warning("Invalid results format. Creating empty results array.");
                $testResults['results'] = [];
            }
            
            // Track execution time
            $executionTime = round((microtime(true) - $startTime) * 1000);
            Log::info("CDN speed test completed in {$executionTime}ms for library: $library, version: $version");
            
            // Log the structure of testResults for debugging
            Log::debug("CDN speed test results structure: " . json_encode(array_keys($testResults)));
            
            // Add user location info to response
            $testResults['user_location'] = [
                'ip' => $userIp,
                'location' => $userLocationInfo
            ];
            
            // Add execution metadata
            $testResults['execution_time'] = $executionTime;
            $testResults['server_timestamp'] = now()->toIso8601String();
            
            // Return the results with appropriate headers
            return response()->json([
                'success' => true,
                'test_results' => $testResults
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
        } catch (\Exception $e) {
            // Log detailed error
            Log::error('CDN speed test error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            
            // Return error response with specific message
            return response()->json([
                'success' => false,
                'message' => 'Error running CDN speed test: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    } 
	/**
 * Display the Website Speed Test tool
 *
 * @return \Illuminate\View\View
 */
public function websiteSpeedTest()
{
    return view('tools.website-speed-test');
}
}