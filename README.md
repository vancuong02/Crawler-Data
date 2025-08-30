# 📘 Product Extraction Guide

Hướng dẫn chi tiết cách trích xuất thông tin sản phẩm từ website.

---

## 📑 Mục lục

1. [Name (Tên sản phẩm)](#1-name-tên-sản-phẩm)
2. [Brand (Thương hiệu)](#2-brand-thương-hiệu)
3. [Model](#3-model)
4. [Related Models](#4-related-models)
5. [Model Alias](#5-model-alias)
6. [Images (Hình ảnh)](#6-hình-ảnh-images)
7. [Category (Danh mục)](#7-category-danh-mục)
8. [Manuals & OtherFiles](#8-manuals--otherfiles)
9. [Specifications](#9-specifications)
10. [Ví dụ kết quả JSON](#10-ví-dụ-kết-quả-json)

---

## 1. Name (Tên sản phẩm)

- Lấy từ thẻ `<h1>` trong HTML.
- Dùng `extract_clearName()` để loại bỏ ký tự thừa.
- **Check name**:
  - Nếu chứa `Value Pack`, `Combo`, `Bundle` → bỏ qua, return data luôn.
  - Nếu hợp lệ → gán vào `$data['name']`.

---

## 2. Brand (Thương hiệu)

- Nếu website chỉ bán 1 thương hiệu duy nhất (ví dụ ThermoPro) → gán trực tiếp:

```json
$data['brand'] = 'ThermoPro';
```

- Nếu website có nhiều thương hiệu → dùng hàm findBrandFromString() để xác định brand từ name hoặc mô tả.

---

## 3. Model

- Thường lấy từ SKU, MPN hoặc trong tiêu đề sản phẩm.

- Nếu SKU chứa hậu tố dạng -W-3, -W-2, -W-4 (đại diện cho combo 2,3,4 packs) → cần loại bỏ phần -W-[0-9] để ra model gốc.

- Ví dụ: SKU IT-TP49-W-3 → Model chuẩn là IT-TP49.

---

## 4. Related Models

- Trường related_models: chứa các model khác có liên quan đến sản phẩm. Có thể lấy gtin, mpn, sku (nếu khác với model).

- Ý nghĩa: gom nhóm các model khác nhau nhưng có cùng tài liệu hoặc cùng dòng sản phẩm để phục vụ tìm kiếm và hiển thị.

- Related có thể bao gồm cả alias.

- Cách sử dụng: khi khách hàng tìm kiếm một model bất kỳ trong nhóm, hệ thống vẫn hiển thị đầy đủ sản phẩm liên quan.

- Ví dụ: Một sản phẩm có model chính là TP49 nhưng cũng có phiên bản TP50. Cả 2 model này sẽ nằm trong related_models.

```json
"related_models": [
  "TP50",       // model nâng cấp, cùng series
  "TP52",       // model khác nhưng chung hướng dẫn
  "TP49-W-2",   // combo 2 pack
  "TP49-W-4"    // combo 4 pack
]
```

---

## 5. Model Alias

- Trường modelAlias: chứa các mã khác tương đương với sản phẩm (alias = tên gọi khác, viết tắt khác).

- Ý nghĩa: giúp hệ thống loại bỏ trùng lặp dữ liệu.

- Khi một alias xuất hiện trong hệ thống (ví dụ từ trang web khác), nó sẽ được nhận diện là cùng một sản phẩm để tránh lưu trùng.

- Ví dụ: Model chính là TP49, còn alias là TP49-W, TP49B. Các alias này đại diện cho cùng sản phẩm đó.

```json
"modelAlias": [
  "TP49-W",   // cách viết khác
  "TP49B",    // bản B nhưng thực chất cùng sản phẩm
  "IT-TP49"   // SKU ghi khác nhưng trùng sản phẩm
]
```

---

## 6. Image

- Lấy từ JSON trong trường images hoặn meta.

- Lưu mảng link ảnh vào $data['images'].

---

## 7. Category (Danh mục)

- Lấy từ breadcrumb hoặc meta tag.
- Nếu không có → xác định theo cấu trúc website.

---

## 8. Manuals & OtherFiles

- Tìm `<a>` chứa link PDF.
- Loại bỏ các file `brochure`, `catalog`, `warranty`, `energy`.
- File chứa từ khóa `manual`, `instruction` → `$data['manualsUrl']`.
- File còn lại → `$data['otherFiles']`.

---

## 9. Specifications

- Lấy từ bảng `<table>`.
- Mỗi `<tr>` gồm:
  - Cột 1 → `name`
  - Cột 2 → `value`
- Loại bỏ giá trị rỗng, `No`, `N/A`.

```json
"specifications": [
  {"name": "Temperature Range", "value": "-58.0 to 158.0℉(-50.0 to 70.0℃)"},
  {"name": "Humidity Range", "value": "10% ~ 99%"}
]
```

## 10. Ví dụ kết quả JSONs

```json
{
  "name": "ThermoPro TP49 Digital Indoor Thermometer Hygrometer",
  "brand": "ThermoPro",
  "category": "Thermometers",
  "model": "TP49",
  "related_models": [
    "TP50", // model nâng cấp, cùng series
    "TP52", // model khác nhưng chung hướng dẫn
    "TP49-W-2", // combo 2 pack
    "TP49-W-4" // combo 4 pack
  ],
  "modelAlias": ["TP49-W", "TP49B", "IT-TP49"],
  "images": ["https://cdn.shopify.com/s/files/.../tp49-front.jpg"],

  "manualsUrl": "https://cdn.shopify.com/s/files/.../ThermoPro-TP-49B_W_Instruction.pdf",
  "specifications": [
    { "name": "Temperature Range", "value": "-58.0 to 158.0℉(-50.0 to 70.0℃)" },
    { "name": "Humidity Range", "value": "10% ~ 99%" }
  ]
}
```
