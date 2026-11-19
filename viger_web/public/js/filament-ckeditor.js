/**
 * Alpine component "ckeditorField" dùng cho App\Filament\Forms\Components\CKEditor.
 * Yêu cầu: window.ClassicEditor (CKEditor 5 Classic build) đã được nạp trước.
 */
(function () {
  function csrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : (window.livewireScriptConfig && window.livewireScriptConfig.csrf) || "";
  }

  // Upload adapter: gửi file lên server (field "upload"), server trả về { url } hoặc { error: { message } }
  class UploadAdapter {
    constructor(loader, url) {
      this.loader = loader;
      this.url = url;
      this.controller = new AbortController();
    }

    async upload() {
      const file = await this.loader.file;
      const fd = new FormData();
      fd.append("upload", file);

      const res = await fetch(this.url, {
        method: "POST",
        headers: { "X-CSRF-TOKEN": csrfToken(), Accept: "application/json" },
        body: fd,
        credentials: "same-origin",
        signal: this.controller.signal,
      });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || data.error || !data.url) {
        throw (data.error && data.error.message) || "Tải ảnh thất bại.";
      }

      return { default: data.url };
    }

    abort() {
      this.controller.abort();
    }
  }

  function register(Alpine) {
    Alpine.data("ckeditorField", ({ state, initialState, uploadUrl, placeholder, disabled }) => {
      // QUAN TRỌNG: KHÔNG lưu instance CKEditor vào data reactive của Alpine.
      // Alpine bọc nó trong Proxy khiến CKEditor lỗi (setData/getData không hoạt động -> nội dung bị mất).
      let editor = null;

      return {
      state,

      async init() {
        if (!window.ClassicEditor) {
          console.error("CKEditor 5 chưa được nạp.");
          return;
        }

        const config = {
          placeholder: placeholder || "",
          toolbar: {
            items: [
              "heading", "|",
              "bold", "italic", "link", "|",
              "bulletedList", "numberedList", "outdent", "indent", "blockQuote", "|",
              ...(uploadUrl ? ["uploadImage"] : []), "insertTable", "mediaEmbed", "|",
              "undo", "redo",
            ],
            shouldNotGroupWhenFull: false,
          },
          image: {
            toolbar: [
              "imageStyle:inline", "imageStyle:block", "imageStyle:side", "|",
              "toggleImageCaption", "imageTextAlternative",
            ],
          },
          table: {
            contentToolbar: ["tableColumn", "tableRow", "mergeTableCells"],
          },
          heading: {
            options: [
              { model: "paragraph", title: "Đoạn văn", class: "ck-heading_paragraph" },
              { model: "heading2", view: "h2", title: "Tiêu đề 2", class: "ck-heading_heading2" },
              { model: "heading3", view: "h3", title: "Tiêu đề 3", class: "ck-heading_heading3" },
              { model: "heading4", view: "h4", title: "Tiêu đề 4", class: "ck-heading_heading4" },
            ],
          },
          mediaEmbed: { previewsInData: true },
          extraPlugins: uploadUrl
            ? [
                function (editor) {
                  editor.plugins.get("FileRepository").createUploadAdapter = (loader) =>
                    new UploadAdapter(loader, uploadUrl);
                },
              ]
            : [],
          // Nạp nội dung có sẵn ngay khi tạo editor (ưu tiên state đã entangle, dự phòng giá trị server render)
          initialData: (typeof this.state === "string" && this.state !== "" ? this.state : initialState) ?? "",
        };

        try {
          editor = await window.ClassicEditor.create(this.$refs.editor, config);
        } catch (e) {
          console.error("Lỗi khởi tạo CKEditor 5:", e);
          return;
        }

        if (disabled) {
          editor.enableReadOnlyMode("filament-disabled");
        }

        // Bảo vệ dữ liệu: nếu state chưa có nhưng editor đã có nội dung (từ initialState) thì đồng bộ lại vào form
        if ((typeof this.state !== "string" || this.state === "") && editor.getData() !== "") {
          this.state = editor.getData();
        }

        // Editor -> Livewire state (chỉ khi người dùng sửa nội dung)
        editor.model.document.on("change:data", () => {
          this.state = editor.getData();
        });

        // Livewire state -> Editor (khi state bị thay đổi từ server, ví dụ reset form)
        this.$watch("state", (value) => {
          if (!editor) return;
          const v = typeof value === "string" ? value : "";
          if (v !== editor.getData()) {
            editor.setData(v);
          }
        });
      },

      destroy() {
        if (editor) {
          editor.destroy().catch(() => {});
          editor = null;
        }
      },
      };
    });
  }

  if (window.Alpine) {
    register(window.Alpine);
  } else {
    document.addEventListener("alpine:init", () => register(window.Alpine));
  }
})();
