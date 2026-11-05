# Thay thuật toán chấm điểm Holland

Web Holland tách **thuật toán chấm điểm** ra sau một interface, nên có thể thay cách tính (thuật toán khác, bộ câu khác) mà **CRM và chatbot không phải sửa**. Điều kiện duy nhất là engine mới trả kết quả đúng cấu trúc **`holland-result/1`** (`docs/HOP_DONG_KET_NOI.md` mục 4.2).

## 1. Các thành phần

| File | Vai trò |
|---|---|
| `app/Contracts/ScoringEngine.php` | Interface mọi engine phải theo |
| `app/Engines/RiasecFitEngineV2.php` | Thuật toán bản chốt (mặc định) |
| `app/Data/HollandResult.php` | Kết quả chấm; `toArray()` ra đúng `holland-result/1` |
| `app/Data/MajorMatch.php` | Một dòng ngành trong `top_university` / `top_vocational` / `ranking` |
| `app/Data/ScoringConfig.php` | Tham số sửa được: trọng số, ngưỡng nhãn, số ngành hiển thị |
| `app/Contracts/MajorCatalog.php` | Nguồn danh mục ngành (`JsonMajorCatalog` đọc `majors.json`; CRM ở giai đoạn 4) |
| `config/holland.php` | `engine` (đang dùng), `engines` (danh sách cài sẵn), `scoring` (tham số mặc định) |

Nơi gọi (trang nộp bài, API `/score`) chỉ dùng interface:

```php
$result = app(ScoringEngine::class)->score($answers, app(MajorCatalog::class)->all());
$json = $result->toArray();               // gửi CRM, lưu attempt
$full = $result->toArray(withRanking: true); // API /score?full=1
```

## 2. Interface `ScoringEngine`

```php
public function name(): string;      // mã engine, ghi vào kết quả, ví dụ "riasec_fit_v3"
public function version(): string;   // ví dụ "3.0"

// Bài làm từng câu: [['group' => 'R', 'value' => 1, 'max' => 1], ...]
public function score(array $answers, Collection $majors): HollandResult;

// Điểm % có sẵn: ['R' => 0.33, 'I' => 0.67, 'A' => 0.89, 'S' => 0.56, 'E' => 0.11, 'C' => 0.22]
public function scorePercent(array $percent, Collection $majors): HollandResult;
```

`$majors` là `Collection` các `App\Data\Major` (gồm cả ngành đang tắt — engine phải tự bỏ ngành `isActive = false`).

## 3. Các bước thêm engine mới

1. Tạo class trong `app/Engines/`, ví dụ `RiasecFitEngineV3`, `implements ScoringEngine`.
   Nhận `ScoringConfig` qua constructor nếu cần trọng số / ngưỡng nhãn.
2. Trả về `new HollandResult(...)` đủ các trường: `percent` (6 nhóm, 0–1), `code` (3 chữ), `di3`, `consistency`, `hasTie`, `topUniversity`, `topVocational`, `fields`, `testas`, `ranking`.
3. Đăng ký trong `config/holland.php`:
   ```php
   'engines' => [
       'riasec_fit_v2' => App\Engines\RiasecFitEngineV2::class,
       'riasec_fit_v3' => App\Engines\RiasecFitEngineV3::class,
   ],
   ```
4. Chọn engine: đặt `HOLLAND_ENGINE=riasec_fit_v3` trong `.env` (từ giai đoạn 7: chọn trong trang Cài đặt), rồi `php artisan config:clear`.
5. Viết test:
   - test riêng cho công thức mới (dữ liệu kỳ vọng tính tay hoặc từ Excel);
   - chạy lại toàn bộ `php artisan test` — test cấu trúc `holland-result/1` phải vẫn đạt.

Ví dụ engine tối giản: `tests/Fakes/FakeScoringEngine.php` (dùng trong `tests/Feature/ScoringEngineBindingTest.php` để chứng minh thay được).

## 4. Thuật toán mặc định `RiasecFitEngineV2`

Port nguyên công thức từ `database/seeders/data/holland/holland_engine_reference.php` (giải thích: `docs/Holland_de_xuat_nganh_v2.1.docx`). **Không đổi công thức.**

1. `p[g]` = tổng điểm câu nhóm g / tổng điểm tối đa nhóm g (mọi số câu, mọi thang trả lời).
2. Mã = 3 nhóm `p` cao nhất; bằng nhau xếp theo R, I, A, S, E, C.
3. `di3 = p1 − (p2 + p4)/2` (< ngưỡng 0,15 = hồ sơ phẳng); `consistency = s(X1, X2)`; `has_tie` khi p1 = p2 hoặc p2 = p3 hoặc p3 = p4.
4. `s(x, y) = 3 − min(|i − j|, 6 − |i − j|)` theo thứ tự RIASEC.
5. `code_match = (3·s(X1,Y1) + 2·s(X2,Y2) + s(X3,Y3)) / 18`.
6. `r = (3·p[Y1] + 2·p[Y2] + p[Y3] − Σp) / (√8 · √Σ(p − p̄)²)` (mẫu số 0 thì r = 0); `score_match = (r + 1)/2`.
7. `FIT = 0,6·code_match + 0,4·score_match`; ngành nhiều mã lấy mã cho FIT cao nhất (`code_used`).
8. Nhãn theo ngưỡng: ≥ 0,80 Rất phù hợp · ≥ 0,65 Phù hợp · ≥ 0,50 Có thể cân nhắc · còn lại Ít phù hợp.
9. Xếp hạng ngành đang bật: FIT giảm → khớp mã giảm → mã ngành tăng; top 5 đại học + top 3 học nghề.
10. Điểm lĩnh vực / nhóm TestAS = FIT cao nhất trong nhóm (TestAS tính cả nhóm chính và phụ).

### Kiểm tra

| Test | Nội dung |
|---|---|
| `tests/Unit/Engines/RiasecFitEngineV2TestCasesTest.php` | 5 học sinh trong `test_cases.json`, sai số 0,001 |
| `tests/Unit/Engines/RiasecFitEngineV2ReferenceTest.php` | So với file tham chiếu PHP trên 300 hồ sơ ngẫu nhiên |
| `tests/Unit/Engines/RiasecFitEngineV2Test.php` | Bằng điểm, hồ sơ phẳng, ngành 3 mã, ngành tắt, thang Likert, cấu trúc kết quả |
| `tests/Feature/ScoringEngineBindingTest.php` | Chọn engine bằng cấu hình, thay bằng engine giả |

```bash
php artisan test --filter=RiasecFitEngineV2
```
