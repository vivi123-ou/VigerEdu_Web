<?php

namespace App\Modules\Schools\Services;

use App\Modules\Assessment\Models\Major;
use App\Modules\Schools\Models\School;
use App\Modules\Schools\Models\SchoolProgram;
use App\Support\SpreadsheetImport;
use Illuminate\Support\Collection;

/**
 * Làm gì:   Nhập danh sách trường – ngành (chương trình học) từ Excel: mỗi dòng một trường + một ngành (+ bậc).
 *           Tìm trường theo mã / tên / tên viết tắt / tên thường dùng; tìm ngành theo mã / tên Việt / tên Đức (không phân biệt hoa thường).
 *           Đã có (cùng trường, ngành, bậc) thì cập nhật, chưa có thì thêm; không tìm thấy trường / ngành thì bỏ qua và báo dòng nào.
 * Ai gọi:   trang "Trường & vùng" (thẻ Ngành của trường → "Nhập từ Excel"), test.
 * Dữ liệu:  schools, majors (chỉ đọc), school_programs.
 */
class ProgramImport extends SpreadsheetImport
{
    /** @var Collection<string, int>|null */
    private ?Collection $schools = null;

    /** @var Collection<string, int>|null */
    private ?Collection $majors = null;

    public function fields(): array
    {
        return SchoolProgram::IMPORTABLE;
    }

    public function required(): array
    {
        return ['school', 'major'];
    }

    public function example(): array
    {
        return ['Technische Universität München', 'Informatik', 'Bachelor', 'Informatik (B.Sc.)', 'Deutsch', 'Vollzeit',
            'https://www.tum.de/studium/studienangebot/detail/informatik-bachelor-of-science-bsc', 'có', ''];
    }

    protected function aliases(): array
    {
        return [
            'school' => ['truong', 'ten_truong', 'ma_truong', 'school', 'hochschule', 'university'],
            'major' => ['nganh', 'ten_nganh', 'ma_nganh', 'major', 'studiengang', 'studienfach', 'fach'],
            'degree' => ['bac', 'bac_hoc', 'bang', 'degree', 'abschluss'],
            'program_name' => ['ten_chuong_trinh', 'chuong_trinh', 'program', 'program_name'],
            'language' => ['ngon_ngu', 'ngon_ngu_day', 'language', 'sprache', 'unterrichtssprache'],
            'study_form' => ['hinh_thuc_hoc', 'study_form', 'studienform'],
            'url' => ['link', 'lien_ket', 'url', 'website'],
            'is_verified' => ['da_kiem_tra', 'kiem_tra', 'verified'],
            'note' => ['ghi_chu', 'note', 'notes'],
        ];
    }

    protected function importRow(array $values, array $extra, array $context): array
    {
        if ($values['school'] === null || $values['major'] === null) {
            return ['skipped', 'thiếu tên trường hoặc tên ngành'];
        }
        $schoolId = $this->schoolLookup()->get(mb_strtolower($values['school']));
        if ($schoolId === null) {
            return ['skipped', 'không tìm thấy trường "'.$values['school'].'"'];
        }
        $majorId = $this->majorLookup()->get(mb_strtolower($values['major']));
        if ($majorId === null) {
            return ['skipped', 'không tìm thấy ngành "'.$values['major'].'" (thêm ngành ở "Ngành học & nghề gợi ý" trước)'];
        }
        $url = $values['url'];
        if ($url !== null && ! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $program = SchoolProgram::query()->firstOrNew([
            'school_id' => $schoolId, 'major_id' => $majorId, 'degree' => (string) $values['degree'],
        ]);
        $program->fill(array_filter([
            'program_name' => $values['program_name'], 'language' => $values['language'], 'study_form' => $values['study_form'],
            'url' => $url, 'note' => $values['note'],
        ], fn ($v) => $v !== null) + ['is_verified' => self::truthy($values['is_verified']), 'extra' => $extra ?: null]);
        $created = ! $program->exists;
        $program->save();

        return [$created ? 'created' : 'updated'];
    }

    /**
     * @return Collection<string, int> chữ thường của mã / tên / tên viết tắt => id trường
     */
    private function schoolLookup(): Collection
    {
        return $this->schools ??= School::query()->get(['id', 'external_code', 'name', 'short_name', 'display_name'])
            ->flatMap(fn (School $s) => collect([$s->external_code, $s->short_name, $s->display_name, $s->name])
                ->filter()->mapWithKeys(fn (string $key) => [mb_strtolower(trim($key)) => $s->id]));
    }

    /**
     * @return Collection<string, int> chữ thường của mã / tên Việt / tên Đức / tên Anh => id ngành
     */
    private function majorLookup(): Collection
    {
        return $this->majors ??= Major::query()->get(['id', 'code', 'name_vi', 'name_de', 'name_en'])
            ->flatMap(fn (Major $m) => collect([$m->name_en, $m->name_de, $m->name_vi, $m->code])
                ->filter()->mapWithKeys(fn (string $key) => [mb_strtolower(trim($key)) => $m->id]));
    }
}
