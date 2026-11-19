<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>{{ $post ? 'Chỉnh sửa bài viết' : 'Soạn bài viết mới' }} - VIGER Admin</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <!-- CKEditor 5 Classic Build -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <style>
      :root {
        --primary: #2563eb;
        --primary-hover: #1d4ed8;
        --bg-body: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-dark: #0f172a;
        --text-muted: #64748b;
      }
      * { box-sizing: border-box; margin: 0; padding: 0; }
      body {
        font-family: "Plus Jakarta Sans", sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
        line-height: 1.6;
        padding: 30px 20px;
      }
      .container { max-width: 1100px; margin: 0 auto; }
      .header-bar {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);
      }
      .header-title h1 { font-size: 24px; font-weight: 700; color: var(--text-dark); }
      .header-title p { font-size: 14px; color: var(--text-muted); }
      .btn {
        display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
        border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer;
        text-decoration: none; transition: all 0.2s ease; border: none;
      }
      .btn-primary { background-color: var(--primary); color: #ffffff; }
      .btn-primary:hover { background-color: var(--primary-hover); }
      .btn-outline { background-color: #ffffff; color: var(--text-dark); border: 1px solid var(--border-color); }
      .btn-outline:hover { background-color: #f1f5f9; }
      .card {
        background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border-color);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); padding: 30px; margin-bottom: 30px;
      }
      .alert-error {
        background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;
        border-radius: 8px; padding: 12px 18px; margin-bottom: 20px; font-size: 14px;
      }
      .alert-error ul { margin: 6px 0 0 18px; }
      .form-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
      @media (max-width: 860px) { .form-grid { grid-template-columns: 1fr; } }
      .form-group { margin-bottom: 20px; }
      .form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark); }
      .form-control {
        width: 100%; padding: 10px 14px; border: 1px solid var(--border-color);
        border-radius: 8px; font-family: inherit; font-size: 14px;
        transition: border-color 0.2s; background-color: #ffffff;
      }
      .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
      textarea.form-control { min-height: 90px; resize: vertical; }

      /* CKEditor custom styles */
      .ck-editor__editable_inline { min-height: 420px; border-radius: 0 0 8px 8px !important; font-size: 15px; line-height: 1.7; }
      .ck-toolbar { border-radius: 8px 8px 0 0 !important; background-color: #f8fafc !important; border-color: var(--border-color) !important; }

      .sidebar-section { background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
      .sidebar-title { font-size: 15px; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
      .badge-status { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header-bar">
        <div class="header-title">
          <h1>{{ $post ? 'Chỉnh sửa bài viết' : 'Soạn bài viết mới' }}</h1>
          <p>Trình soạn thảo văn bản CKEditor 5 cho Admin</p>
        </div>
        <div style="display: flex; gap: 10px">
          <a href="{{ url('/admin/posts') }}" class="btn btn-outline">
            <i class="fa-solid fa-arrow-left"></i> Danh sách bài viết
          </a>
          <button type="submit" form="postForm" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Lưu bài viết
          </button>
        </div>
      </div>

      {{-- Lỗi validate từ server --}}
      @if ($errors->any())
        <div class="alert-error">
          <i class="fa-solid fa-circle-exclamation"></i> Không thể lưu bài viết:
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="postForm" action="{{ url('/admin/posts/save') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ old('id', $post?->id) }}" />

        <div class="form-grid">
          <!-- Left Main Column: Title & CKEditor Content -->
          <div>
            <div class="card">
              <div class="form-group">
                <label for="title">Tiêu đề bài viết <span style="color: red">*</span></label>
                <input
                  type="text"
                  id="title"
                  name="title"
                  value="{{ old('title', $post?->title) }}"
                  class="form-control"
                  placeholder="Nhập tiêu đề bài viết..."
                  required
                />
              </div>

              <div class="form-group">
                <label for="excerpt">Tóm tắt ngắn (Excerpt)</label>
                <textarea
                  id="excerpt"
                  name="excerpt"
                  class="form-control"
                  placeholder="Tóm tắt nội dung bài viết hiển thị ở trang danh sách blog..."
                >{{ old('excerpt', $post?->excerpt) }}</textarea>
              </div>

              <div class="form-group">
                <label for="editor">
                  Nội dung chi tiết (Rich Text Content)
                  <span style="color: red">*</span>
                </label>
                <!-- Textarea được đồng bộ trực tiếp với CKEditor 5 -->
                <textarea
                  id="editor"
                  name="content"
                  placeholder="Bắt đầu soạn thảo nội dung tại đây..."
                >{{ old('content', $post?->content) }}</textarea>
              </div>
            </div>
          </div>

          <!-- Right Sidebar: Slug, Category, Featured Image, Status -->
          <div>
            <div class="card">
              <div class="sidebar-section">
                <div class="sidebar-title">
                  <i class="fa-solid fa-sliders"></i> Cài đặt xuất bản
                </div>

                @php
                  $currentStatus = old('status', $post?->status?->value ?? 'PUBLISHED');
                  $currentCategory = old('category_id', $post?->category_id);
                @endphp

                <div class="form-group">
                  <label for="status">Trạng thái</label>
                  <select id="status" name="status" class="form-control">
                    <option value="PUBLISHED" @selected($currentStatus === 'PUBLISHED')>Xuất bản (PUBLISHED)</option>
                    <option value="DRAFT" @selected($currentStatus === 'DRAFT')>Bản nháp (DRAFT)</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="category">Chuyên mục</label>
                  <select id="category" name="category_id" class="form-control">
                    <option value="">-- Chọn chuyên mục --</option>
                    @foreach ($categories as $cat)
                      <option value="{{ $cat->id }}" @selected((string) $currentCategory === (string) $cat->id)>
                        {{ $cat->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="sidebar-section">
                <div class="sidebar-title">
                  <i class="fa-solid fa-link"></i> Đường dẫn tĩnh (Slug)
                </div>
                <div class="form-group">
                  <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $post?->slug) }}"
                    class="form-control"
                    placeholder="Tự động tạo từ tiêu đề nếu để trống"
                  />
                  <small style="color: var(--text-muted); font-size: 12px; display: block; margin-top: 5px;">
                    Ví dụ: <code>hoc-phi-dai-hoc-duc-2026</code>
                  </small>
                </div>
              </div>

              <div class="sidebar-section">
                <div class="sidebar-title">
                  <i class="fa-solid fa-image"></i> Ảnh đại diện (Featured Image)
                </div>
                <div class="form-group">
                  <input
                    type="text"
                    id="featuredImage"
                    name="featured_image"
                    value="{{ old('featured_image', $post?->featured_image) }}"
                    class="form-control"
                    placeholder="URL ảnh"
                  />
                </div>
                <label class="btn btn-outline" style="cursor: pointer; padding: 8px 14px">
                  <i class="fa-solid fa-upload"></i> Tải ảnh lên
                  <input type="file" id="featuredFile" accept="image/*" hidden />
                </label>
                <img
                  id="featuredPreview"
                  alt=""
                  style="display: none; width: 100%; margin-top: 12px; border-radius: 8px; border: 1px solid var(--border-color);"
                />
              </div>

              <button
                type="submit"
                class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 10px"
              >
                <i class="fa-solid fa-paper-plane"></i> Hoàn tất &amp; Lưu CSDL
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Khởi tạo CKEditor 5 -->
    <script>
      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
      const CSRF_HEADER = "X-CSRF-TOKEN"; // Laravel đọc token từ header này
      const UPLOAD_URL = "/admin/posts/api/upload-image";

      // Upload 1 file, trả về URL
      async function uploadFile(file) {
        const fd = new FormData();
        fd.append("upload", file);
        const res = await fetch(UPLOAD_URL, {
          method: "POST",
          headers: { [CSRF_HEADER]: csrfToken, Accept: "application/json" },
          body: fd,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.error) {
          throw new Error(
            (data.error && data.error.message) || "Tải ảnh thất bại",
          );
        }
        return data.url;
      }

      // Tạo slug không dấu từ tiêu đề
      function toSlug(s) {
        return s
          .normalize("NFD")
          .replace(/[\u0300-\u036f]/g, "")
          .replace(/đ/g, "d")
          .replace(/Đ/g, "D")
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, "-")
          .replace(/^-+|-+$/g, "");
      }

      // Tự điền slug khi gõ tiêu đề (chỉ khi người dùng chưa tự sửa slug)
      const titleInput = document.getElementById("title");
      const slugInput = document.getElementById("slug");
      let slugTouched = slugInput.value.trim() !== "";
      slugInput.addEventListener("input", () => {
        slugTouched = slugInput.value.trim() !== "";
      });
      titleInput.addEventListener("input", () => {
        if (!slugTouched) slugInput.value = toSlug(titleInput.value);
      });

      // Ảnh đại diện: xem trước + tải lên
      const featuredInput = document.getElementById("featuredImage");
      const featuredPreview = document.getElementById("featuredPreview");
      const featuredFile = document.getElementById("featuredFile");

      function refreshPreview() {
        const v = featuredInput.value.trim();
        featuredPreview.style.display = v ? "block" : "none";
        if (v) featuredPreview.src = v;
      }
      featuredInput.addEventListener("input", refreshPreview);
      refreshPreview();

      featuredFile.addEventListener("change", async () => {
        const file = featuredFile.files[0];
        if (!file) return;
        try {
          featuredInput.value = await uploadFile(file);
          refreshPreview();
        } catch (e) {
          alert(e.message);
        } finally {
          featuredFile.value = "";
        }
      });

      // CKEditor 5
      ClassicEditor.create(document.querySelector("#editor"), {
        toolbar: {
          items: [
            "heading", "|",
            "bold", "italic", "link", "|",
            "bulletedList", "numberedList", "blockQuote", "|",
            "uploadImage", "insertTable", "mediaEmbed", "|",
            "undo", "redo",
          ],
        },
        simpleUpload: {
          uploadUrl: UPLOAD_URL,
          headers: { [CSRF_HEADER]: csrfToken, Accept: "application/json" },
        },
        image: {
          toolbar: [
            "imageStyle:inline",
            "imageStyle:block",
            "imageStyle:side",
            "|",
            "toggleImageCaption",
            "imageTextAlternative",
          ],
        },
        heading: {
          options: [
            { model: "paragraph", title: "Đoạn văn", class: "ck-heading_paragraph" },
            { model: "heading2", view: "h2", title: "Tiêu đề 2", class: "ck-heading_heading2" },
            { model: "heading3", view: "h3", title: "Tiêu đề 3", class: "ck-heading_heading3" },
          ],
        },
      })
        .then((editor) => {
          window.editor = editor;

          document.querySelector("#postForm").addEventListener("submit", (e) => {
            editor.updateSourceElement(); // đồng bộ HTML vào textarea
            if (editor.getData().trim() === "") {
              // chặn nội dung rỗng
              e.preventDefault();
              alert("Vui lòng nhập nội dung bài viết.");
              editor.editing.view.focus();
            }
          });
        })
        .catch((err) => console.error("Lỗi khởi tạo CKEditor 5:", err));
    </script>
  </body>
</html>