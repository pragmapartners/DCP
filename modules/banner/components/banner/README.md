# Banner Component

## Description
A banner component that renders a customizable banner with buttons.

## Properties

### Attributes
- **Type**: Object
- **Description**: A set of HTML attributes to apply to the banner.
- **Properties**:
  - **Class**:
    - **Type**: Array
    - **Items**:
      - **Type**: String
    - **Description**: A list of CSS classes to apply.

### Banner Heading
- **Type**: String
- **Description**: The heading text for the banner.

### Banner Body
- **Type**: String
- **Description**: The body content of the banner.

### Body Format
- **Type**: String
- **Description**: The text format for the banner body (e.g., basic_html, full_html).

### Banner Color
- **Type**: String
- **Description**: The predefined color theme for the banner.
- **Enum**:
  - default
  - custom

### Banner Color Picker
- **Type**: String
- **Description**: A hex code for a custom banner color (if applicable).

### Buttons
- **Type**: Array
- **Description**: A list of buttons to display in the banner.
- **Items**:
  - **Type**: Object
  - **Properties**:
    - **ID**:
      - **Type**: Integer
      - **Description**: The unique identifier for the button.
    - **Button Title**:
      - **Type**: String
      - **Description**: The label for the button.
    - **Button URL**:
      - **Type**: String
      - **Description**: The URL the button links to.
      - **Format**: URI
