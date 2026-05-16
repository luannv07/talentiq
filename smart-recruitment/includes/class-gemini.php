<?php
if (!defined('ABSPATH')) exit;

class SR_Gemini {

    private $api_key;
    private $model;
    private $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct() {
        $this->api_key = trim(get_option('sr_gemini_api_key', ''));
        $this->model   = get_option('sr_gemini_model', 'gemini-2.5-flash');
    }

    public function analyze_cv($cv_path, $job_title, $job_requirements) {

    if (empty($this->api_key)) {
        return ['error' => 'Chưa cài đặt Gemini API key'];
    }

    $cv_content = $this->extract_cv_text($cv_path);
    if (!$cv_content) {
        return ['error' => 'Không thể đọc nội dung CV'];
    }

    $cv_content = mb_substr($cv_content, 0, 3000);

    $prompt = "Bạn là chuyên gia tuyển dụng HR chuyên nghiệp. Nhiệm vụ: phân tích CV ứng viên và trả về ĐÚNG format bên dưới — không thêm bất kỳ nội dung nào khác ngoài 4 dòng đó.\n\n"
        . "VỊ TRÍ TUYỂN DỤNG: {$job_title}\n\n"
        . "YÊU CẦU TUYỂN DỤNG:\n{$job_requirements}\n\n"
        . "NỘI DUNG CV ỨNG VIÊN:\n{$cv_content}\n\n"
        . "OUTPUT BẮT BUỘC — đúng 4 dòng, đúng thứ tự, đúng tiền tố:\n"
        . "SCORE: <số nguyên 0-100, không có ký tự thừa>\n"
        . "STRENGTHS: <điểm mạnh 1> | <điểm mạnh 2> | <điểm mạnh 3>\n"
        . "WEAKNESSES: <điểm yếu 1> | <điểm yếu 2>\n"
        . "RECOMMENDATION: <nhận xét 2-3 câu đầy đủ, dùng **từ quan trọng** để nhấn mạnh>\n\n"
        . "QUY TẮC CHẤM ĐIỂM:\n"
        . "- SCORE 80-100: CV khớp trực tiếp với phần lớn yêu cầu.\n"
        . "- SCORE 50-79: CV có liên quan nhưng còn thiếu kỹ năng quan trọng.\n"
        . "- SCORE 0-49: CV không liên quan hoặc thiếu hầu hết yêu cầu cốt lõi.\n"
        . "KHÔNG viết thêm tiêu đề, ghi chú, hay bất kỳ dòng nào ngoài 4 dòng trên.";

    $body = [
        'contents' => [[
            'parts' => [['text' => $prompt]]
        ]],
        'generationConfig' => [
            'temperature'     => 0.2,
            'maxOutputTokens' => 4096,
        ],
    ];

    $api_url  = $this->api_base . urlencode($this->model) . ':generateContent';
    $response = wp_remote_post(
        $api_url . '?key=' . urlencode($this->api_key),
        [
            'method'  => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
            'timeout' => 90,
        ]
    );

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message()];
    }

    $status_code   = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);

    if ($status_code !== 200) {
        $err = json_decode($response_body, true);
        $msg = $err['error']['message'] ?? "HTTP {$status_code}";
        return ['error' => 'Gemini API lỗi: ' . $msg];
    }

    $data  = json_decode($response_body, true);
    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    $text  = '';
    foreach ($parts as $part) {
        if (!empty($part['thought'])) continue;
        if (!empty($part['text'])) {
            $text = $part['text'];
            break;
        }
    }

    if (empty($text)) {
        return ['error' => 'Gemini không trả dữ liệu hợp lệ'];
    }

    $score          = 0;
    $strengths      = [];
    $weaknesses     = [];
    $recommendation = '';
    $in_rec         = false;

    foreach (explode("\n", $text) as $line) {
        $trimmed = trim($line);
        if (stripos($trimmed, 'SCORE:') === 0) {
            $in_rec = false;
            $score  = intval(preg_replace('/[^0-9]/', '', substr($trimmed, 6)));
        } elseif (stripos($trimmed, 'STRENGTHS:') === 0) {
            $in_rec    = false;
            $raw       = trim(substr($trimmed, 10));
            $strengths = array_filter(array_map('trim', explode('|', $raw)));
        } elseif (stripos($trimmed, 'WEAKNESSES:') === 0) {
            $in_rec     = false;
            $raw        = trim(substr($trimmed, 11));
            $weaknesses = array_filter(array_map('trim', explode('|', $raw)));
        } elseif (stripos($trimmed, 'RECOMMENDATION:') === 0) {
            $in_rec         = true;
            $recommendation = trim(substr($trimmed, 15));
        } elseif ($in_rec && $trimmed !== '') {
            $recommendation .= ' ' . $trimmed;
        }
    }

    return [
        'score'          => $score,
        'strengths'      => array_values($strengths),
        'weaknesses'     => array_values($weaknesses),
        'recommendation' => $recommendation,
    ];
}

    public function generate_jd($title, $skills, $location, $type, $salary) {
        if (empty($this->api_key)) {
            return ['error' => 'Chưa cài đặt Gemini API key'];
        }

        $prompt = "Bạn là chuyên gia tuyển dụng HR. Viết JD tiếng Việt chuyên nghiệp theo ĐÚNG format dưới đây — không thêm, không bỏ bất kỳ phần nào.\n\n"
            . "THÔNG TIN:\n"
            . "- Vị trí: {$title}\n"
            . "- Kỹ năng yêu cầu: {$skills}\n"
            . "- Địa điểm: {$location}\n"
            . "- Hình thức: {$type}\n"
            . "- Mức lương: {$salary}\n\n"
            . "OUTPUT BẮT BUỘC — đúng 2 phần, đúng tiêu đề:\n\n"
            . "MÔ TẢ:\n"
            . "<Viết đầy đủ 250-350 từ. Trình bày rõ: tổng quan vai trò, trách nhiệm chính (ít nhất 5 đầu việc cụ thể), môi trường làm việc và cơ hội phát triển. Không dùng markdown, không bullet ký tự đặc biệt — chỉ dùng số thứ tự hoặc gạch đầu dòng bằng dấu ->\n\n"
            . "YÊU CẦU:\n"
            . "<Liệt kê ít nhất 6 yêu cầu cụ thể, mỗi yêu cầu 1 dòng bắt đầu bằng dấu -. Bao gồm: kỹ năng kỹ thuật, kinh nghiệm, trình độ học vấn, kỹ năng mềm. Đây là tiêu chí để AI chấm điểm CV.>\n\n"
            . "KHÔNG viết thêm lời dẫn, ghi chú, hay nội dung nào ngoài 2 phần trên.";

        $body = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 3000],
        ];

        $api_url  = $this->api_base . urlencode($this->model) . ':generateContent';
        $response = wp_remote_post(
            $api_url . '?key=' . urlencode($this->api_key),
            ['method' => 'POST', 'headers' => ['Content-Type' => 'application/json'], 'body' => wp_json_encode($body), 'timeout' => 60]
        );

        if (is_wp_error($response)) return ['error' => $response->get_error_message()];

        $status_code   = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            $err = json_decode($response_body, true);
            return ['error' => 'Gemini API lỗi: ' . ($err['error']['message'] ?? "HTTP {$status_code}")];
        }

        $data  = json_decode($response_body, true);
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $text  = '';
        foreach ($parts as $part) {
            if (!empty($part['thought'])) continue;
            if (!empty($part['text'])) { $text = $part['text']; break; }
        }

        if (empty($text)) return ['error' => 'Gemini không trả dữ liệu hợp lệ'];

        $text         = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $description  = $text;
        $requirements = '';

        if (preg_match('/YÊU CẦU:/i', $text)) {
            $split        = preg_split('/YÊU CẦU:/i', $text, 2);
            $desc_raw     = preg_replace('/^.*?MÔ TẢ:\s*/is', '', $split[0] ?? '');
            $description  = trim($desc_raw);
            $requirements = trim($split[1] ?? '');
        }

        return ['description' => $description, 'requirements' => $requirements];
    }

    private function extract_cv_text($cv_path) {
        if (!file_exists($cv_path)) return false;
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($cv_path);
            $text   = $pdf->getText();
            if (empty(trim($text))) return false;
            // Clean text trước khi gửi Gemini
            $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
            $text = preg_replace('/\s+/', ' ', $text); // collapse whitespace
            $text = trim($text);
            return mb_substr($text, 0, 3000);
        } catch (\Exception $e) {
            return false;
        }
    }
}
