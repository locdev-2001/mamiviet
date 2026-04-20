# Frontend Integration Guide - Hierarchical Menu Display

## Overview
API hiện tại đã hỗ trợ cấu trúc menu phân cấp. Frontend cần sử dụng 2 endpoint có sẵn và tự xử lý logic hiển thị theo yêu cầu.

## API Endpoints

### 1. GET /user/menu-categories

**Mặc định trả về cấu trúc cây phân cấp:**

**Response Structure:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Sushi",
      "description": "Japanese sushi collection", 
      "parent_id": null,
      "is_parent": true,
      "is_child": false,
      "position": 1,
      "menu_items_count": 3,
      "children": [
        {
          "id": 2,
          "name": "Sushi Tươi",
          "description": "Fresh sushi",
          "parent_id": 1,
          "is_parent": false,
          "is_child": true,
          "position": 1,
          "menu_items_count": 2,
          "children": []
        },
        {
          "id": 3,
          "name": "Sushi Chín", 
          "parent_id": 1,
          "menu_items_count": 1,
          "children": []
        }
      ]
    }
  ]
}
```

### 2. GET /user/menu-items

**Trả về danh sách flat như cũ:**

**Response Structure:**
```json
{
  "data": [
    {
      "id": 101,
      "name": "California Roll",
      "description": "Classic sushi roll",
      "menu_category_id": 2,  // Belongs to "Sushi Tươi"
      "category": {
        "id": 2,
        "name": "Sushi Tươi",
        "parent_id": 1
      },
      "price": 12.99,
      "available": true,
      "images": ["https://example.com/image1.jpg"]
    },
    {
      "id": 102,
      "name": "Salmon Nigiri",
      "menu_category_id": 1,  // Belongs to main "Sushi"
      "category": {
        "id": 1,
        "name": "Sushi",
        "parent_id": null
      },
      "price": 8.99
    }
  ],
  "current_page": 1,
  "per_page": 20,
  "total": 25
}
```

## Frontend Implementation Logic

### Step 1: Fetch Data
```javascript
// Lấy cấu trúc cây categories
const categoriesResponse = await fetch('/user/menu-categories');
const categories = await categoriesResponse.json();

// Lấy tất cả menu items  
const itemsResponse = await fetch('/user/menu-items?per_page=1000');
const items = await itemsResponse.json();
```

### Step 2: Group Items by Main Category

```javascript
function groupItemsByMainCategory(categories, items) {
  const result = {};
  
  // Tạo map category ID -> main category ID
  const categoryToMainCategoryMap = {};
  
  categories.data.forEach(mainCategory => {
    // Main category
    categoryToMainCategoryMap[mainCategory.id] = mainCategory.id;
    
    // Subcategories
    mainCategory.children.forEach(subCategory => {
      categoryToMainCategoryMap[subCategory.id] = mainCategory.id;
    });
  });
  
  // Group items theo main category
  items.data.forEach(item => {
    const mainCategoryId = categoryToMainCategoryMap[item.menu_category_id];
    
    if (mainCategoryId) {
      if (!result[mainCategoryId]) {
        result[mainCategoryId] = [];
      }
      result[mainCategoryId].push(item);
    }
  });
  
  return result;
}

const groupedItems = groupItemsByMainCategory(categories, items);
```

### Step 3: Display Menu

```javascript
function displayMenu(categories, groupedItems) {
  categories.data.forEach(mainCategory => {
    console.log(`\n📁 ${mainCategory.name} (${groupedItems[mainCategory.id]?.length || 0} items)`);
    
    // Hiển thị items thuộc main category này
    const categoryItems = groupedItems[mainCategory.id] || [];
    
    categoryItems.forEach(item => {
      // Kiểm tra xem item thuộc subcategory hay main category
      if (item.category.parent_id) {
        console.log(`  🍽️ ${item.name} (từ ${item.category.name})`);
      } else {
        console.log(`  🍽️ ${item.name}`);
      }
    });
  });
}

displayMenu(categories, groupedItems);
```

### Step 4: Category Filter Implementation

```javascript
function createCategoryFilter(categories, groupedItems) {
  const filters = [];
  
  categories.data.forEach(mainCategory => {
    const itemCount = groupedItems[mainCategory.id]?.length || 0;
    
    const filter = {
      id: mainCategory.id,
      name: mainCategory.name,
      count: itemCount,
      children: []
    };
    
    // Thêm subcategory filters
    mainCategory.children.forEach(subCategory => {
      const subItems = groupedItems[mainCategory.id]?.filter(
        item => item.menu_category_id === subCategory.id
      ) || [];
      
      filter.children.push({
        id: subCategory.id,
        name: subCategory.name,
        count: subItems.length,
        parentId: mainCategory.id
      });
    });
    
    filters.push(filter);
  });
  
  return filters;
}

// Usage
const categoryFilters = createCategoryFilter(categories, groupedItems);
```

### Step 5: Filter Items by Category

```javascript
function filterItemsByCategory(groupedItems, mainCategoryId, subCategoryId = null) {
  const categoryItems = groupedItems[mainCategoryId] || [];
  
  if (subCategoryId) {
    // Filter by subcategory
    return categoryItems.filter(item => item.menu_category_id === subCategoryId);
  }
  
  // Return all items in main category
  return categoryItems;
}

// Examples:
// Tất cả items trong Sushi category
const sushiItems = filterItemsByCategory(groupedItems, 1);

// Chỉ items từ Sushi Tươi subcategory  
const freshSushiItems = filterItemsByCategory(groupedItems, 1, 2);
```

## Expected Display Structure

```
📁 Sushi (5 items)
  🍽️ California Roll (từ Sushi Tươi)
  🍽️ Salmon Roll (từ Sushi Tươi)  
  🍽️ Teriyaki Roll (từ Sushi Chín)
  🍽️ Tempura Roll (từ Sushi Chín)
  🍽️ Nigiri Set

📁 Pizza (3 items)
  🍽️ Margherita
  🍽️ Pepperoni  
  🍽️ Hawaiian
```

## Category Filter UI

```
☐ Sushi (5)
  ☐ Sushi Tươi (2)
  ☐ Sushi Chín (2)
☐ Pizza (3)  
☐ Pasta (4)
  ☐ Pasta Ý (2)
  ☐ Pasta Việt (2)
```

## Performance Notes

1. **One-time fetch**: Lấy tất cả categories và items một lần
2. **Client-side grouping**: Xử lý grouping ở frontend cho performance tốt hơn
3. **Caching**: Cache data để tránh API calls liên tục
4. **Pagination**: Nếu có quá nhiều items, implement virtual scrolling

## Backward Compatibility

Existing frontend code vẫn hoạt động bình thường:
```javascript
// Existing code continues to work
const items = await fetch('/user/menu-items').then(r => r.json());
const categories = await fetch('/user/menu-categories?hierarchical=false').then(r => r.json());
```

## Summary

- **API không thay đổi** - chỉ cần dùng 2 endpoint có sẵn
- **Frontend tự xử lý** logic grouping và display  
- **Linh hoạt** - có thể customize display logic theo ý muốn
- **Performance tốt** - client-side processing, ít API calls