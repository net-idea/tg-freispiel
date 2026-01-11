# Hütte9 - Page Features Guide

## 🏠 Homepage (/)

### Hero Section

```
┌─────────────────────────────────────────────────┐
│                                                 │
│     🏔️                                          │
│     Welcome to Hütte9                          │
│     Your exclusive mountain cabin              │
│                                                 │
│     [Book Now]  [Learn More]                   │
│                                                 │
│              ↓ scroll indicator                 │
└─────────────────────────────────────────────────┘
```

- Full-screen gradient background (purple/blue)
- Animated title and subtitle
- Glowing call-to-action buttons
- Smooth scroll indicator

### Features Section

```
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ 🏔️       │ │ ✨       │ │ 🎿       │ │ 🔥       │
│ Mountain │ │ Modern   │ │Activities│ │  Cozy    │
│  Views   │ │ Comfort  │ │          │ │ Ambiance │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
```

- 4 glass-effect feature cards
- Icons with pulse animation
- Hover lift effect

### About Section

```
┌─────────────┐  ┌────────────────────────────┐
│             │  │ Your Perfect Mountain      │
│   [Image]   │  │ Getaway                    │
│             │  │                            │
│  ⭐Premium  │  │ Welcome to Hütte9...       │
│   Property  │  │                            │
└─────────────┘  │ [Get in Touch]             │
                 └────────────────────────────┘
```

- Image showcase with badge
- Detailed description
- Call-to-action button

### Amenities Section

```
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ 📶 WiFi     │ │ 🍳 Kitchen  │ │ 🛏️ Bedrooms │
└─────────────┘ └─────────────┘ └─────────────┘
┌─────────────┐ ┌─────────────┐ ┌─────────────┐
│ 🅿️ Parking  │ │ 🌡️ Heating  │ │ 🧺 Laundry  │
└─────────────┘ └─────────────┘ └─────────────┘
```

- 6 amenity cards in grid
- Glass effect with icons
- Slide animation on hover

### CTA Section

```
┌─────────────────────────────────────────────────┐
│                                                 │
│   Ready to Experience Hütte9?                  │
│   Book your mountain retreat today             │
│                                                 │
│          [Contact Us Now]                       │
│                                                 │
└─────────────────────────────────────────────────┘
```

- Full-width gradient section
- Large glowing button

---

## 📧 Contact Page (/contact)

### Contact Hero

```
┌─────────────────────────────────────────────────┐
│                                                 │
│          Get in Touch                          │
│   Let's plan your perfect mountain getaway     │
│                                                 │
└─────────────────────────────────────────────────┘
```

- Smaller hero with gradient
- Clear messaging

### Contact Form Layout

```
┌──────────────────┐  ┌─────────────────────────┐
│ Contact Info     │  │ Send us a Message       │
│                  │  │                         │
│ 📧 Email         │  │ [Name]      [Email]     │
│ info@huette9.de  │  │                         │
│                  │  │ [Subject (optional)]    │
│ 📍 Location      │  │                         │
│ Dormagen, DE     │  │ [Message]               │
│                  │  │                         │
│ ⏰ Response      │  │                         │
│ Within 24h       │  │ [Send Message ✉️]       │
│                  │  │                         │
└──────────────────┘  └─────────────────────────┘
```

- Two-column responsive layout
- Sticky contact info on scroll (desktop)
- Glass-effect cards
- Floating labels on inputs
- Full-width submit button

### Stats Section

```
┌────────────────────────────────────────────────┐
│         Why Choose Hütte9?                     │
│                                                │
│   ⭐ 5.0        🏆 100%       💚 24/7          │
│   Rating       Satisfaction   Support         │
└────────────────────────────────────────────────┘
```

- Trust indicators
- Large numbers for impact

---

## 🎨 Theme Switcher

Located in navigation bar:

```
┌─────────────────────────────────┐
│ 🌓 Theme ▼                      │
├─────────────────────────────────┤
│ 💻 System                       │
│ ☀️ Light                         │
│ 🌙 Dark                          │
└─────────────────────────────────┘
```

**Themes:**

1. **System**: Follows your OS preference
2. **Light**: Clean white/light gray with subtle shadows
3. **Dark**: Deep blue/purple with enhanced glow effects

**Features:**

- Instant switching (no page reload)
- Saved in localStorage
- No flash on page load
- Smooth transitions

---

## 🎯 Key Design Elements

### Glass Morphism

- Semi-transparent backgrounds
- Backdrop blur effect
- Subtle borders
- Layered depth

### Animations

- ✨ Fade-in on load
- 🎭 Hover transformations
- 💫 Pulsing icons
- 🌊 Smooth transitions

### Typography

- Bold, large headings
- Clear hierarchy
- Readable body text
- Proper spacing

### Colors

**Light Theme:**

- Background: White → Light Gray gradient
- Primary: Blue (#0d6efd)
- Text: Dark gray/black
- Glass: White (80% opacity)

**Dark Theme:**

- Background: Dark Blue → Purple gradient
- Primary: Same blue (consistency)
- Text: White/light gray
- Glass: White (5% opacity)

---

## 📱 Responsive Design

### Desktop (>992px)

- Two-column layouts
- Full hero sections
- Hover effects active
- Sticky elements

### Tablet (768px-991px)

- Stacked columns
- Adjusted spacing
- Touch-optimized

### Mobile (<768px)

- Single column
- Hamburger menu
- Larger touch targets
- Simplified layouts

---

## ⚡ Performance

- **CSS**: Minified, optimized
- **JS**: Bundle split, async
- **Images**: Placeholders (ready for real images)
- **Fonts**: System fonts (fast)
- **Caching**: Browser cache headers

---

## 🚀 Quick Start

```bash
# Install and run
./develop.sh

# Or manually:
yarn install
composer install
yarn build
symfony server:start

# Visit:
http://127.0.0.1:8000/          # Homepage
http://127.0.0.1:8000/contact   # Contact
```

---

## ✅ Browser Support

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers
- ⚠️ Backdrop-filter: IE not supported (graceful fallback)
