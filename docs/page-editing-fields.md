# Rockpack editor field catalog

Generated from the active editor schemas on 20 October 2026. Field values are omitted. Page, hero and section headings are fixed in `wp-content/themes/rockpack/includes/section-titles.php`.

Duplicate legacy scalar controls, the retired search-dialog title, unused certificate backgrounds and unused subtitle/eyebrow controls have been removed. Saved legacy values are retained as fallback data where needed.

## Appearance > Header & Footer

These controls are shared across the site and are managed in the dedicated Appearance screen.

- Search field label (`nav_search_prompt`; text).
- Home label (`nav_label_home`; text).
- Contact Us label (`nav_label_contact_us`; text).
- Get Started label (`nav_label_get_started`; text).
- Search label (`nav_label_search`; text).
- Navigation brand image (`nav_brand_image`; image).
- Link: / (`shared_link_home`; url).
- Link: /contact-us/ (`shared_link_contact_us`; url).
- Link: /contact-us/#contact-form (`shared_link_contact_us_contact_form`; url).
- Copyright (use {year} for current year) (`footer_copyright`; text).
- Follow us on label (`footer_label_follow_us_on`; text).
- Pages label (`footer_label_pages`; text).
- Address label (`footer_label_address`; text).
- Phone Number label (`footer_label_phone_number`; text).
- Newsletter text (`footer_newsletter_text`; textarea).
- Email field placeholder (`footer_email_placeholder`; text).
- Subscribe button text (`footer_subscribe_text`; text).
- Address (`contact_address`; textarea).
- Phone (`contact_phone`; text).

- **Shared navigation dropdowns** (`navigation_groups`): Dropdown label; Dropdown appearance; Links [Label; Destination].
- **Shared footer page links** (`footer_page_links`): Label; Destination.
- **Shared footer service links** (`footer_service_links`): Label; Destination.
- **Shared footer social links** (`footer_social`): Label; Destination; Icon.

Page components below follow the public website from top to bottom. Fields, images and repeaters are grouped with their matching component.

## Home

### Hero

- **Hero slides** (`hero_images`): Background image; Eyebrow text; Description; Button text; Button URL.

### Statistics

- **Statistics (shared)** (`stats`): Main label; Sub-label; Icon; Custom icon (optional).

### About

- Photo URL (`about_image`; image).
- Image description: Rock Pack Packaging Products (`home_image_description_71`; text).
- Eyebrow (`about_eyebrow`; text).
- Button text (`about_btn_text`; text).
- Button URL (`about_btn_url`; url).
- **Introduction bullets** (`home_bullets`): Text.
- **Capability tabs** (`home_tabs`): Tab label; Tab content.

### History

- Learn-more link text (`history_learn_text`; text).
- Learn-more link URL (`history_learn_url`; url).
- **History slides (shared)** (`history`): Title; Description; Milestone image; Milestone icon; Placement.

### Certifications

- Button text (`certs_btn_text`; text).
- Button URL (`certs_btn_url`; url).
- **Certifications (shared)** (`certs`): Name; Certificate icon.

### Products and Services

- Learn-more link text (`products_learn_text`; text).
- Learn-more link URL (`products_learn_url`; url).
- **Product gallery (shared)** (`products`): Title / image description; Product image; Product link.

### Process Control and Inspection

- Introduction (`process_intro`; textarea).
- **Process cards (shared with Home and Portfolio)** (`process_cards`): Title; Description; Image; Statistic; Statistic label; Card appearance; Image description.

### Localization

- **Localization cards (shared with Home and Portfolio)** (`local_cards`): Title; Description; Image; Card appearance; Image description.

### Planet and Societal Impact

- Subtitle (`planet_subtitle`; text).
- Button text (`planet_btn_text`; text).
- Button URL (`planet_btn_url`; url).
- **Sustainability features** (`home_planet_cards`): Title; Description; Image; Image description; Link.

### News

- Subtitle (`news_subtitle`; text).
- Main image URL (`news_image`; image).
- Image description: The Rock Pack production facility (`home_image_description_72`; text).
- Introduction (`news_intro`; textarea).
- News author label (`news_author_prefix`; text).

Published Posts supply the news cards. Use **Manage news posts** in this component to edit articles, excerpts and featured images. The archive and article pages use their published content.

### Partners

- **Partner logos (shared)** (`logos`): Partner name; Logo; Partner link.

### Careers

- Careers background image URL (`careers_image`; image).
- Section subtitle (`careers_sub`; text).
- Card heading (`careers_card_heading`; textarea).
- Card body (`careers_card_text`; textarea).
- Button text (`careers_card_btn`; text).

Published open job opportunities supply the job links. Use **Manage job opportunities** in this component. The component button automatically opens the Careers archive.

### Closing call to action

- Description (`cta_desc`; textarea).
- Button text (`cta_btn_text`; text).
- Button URL (`cta_btn_url`; url).

## About Us

### Hero

- Hero image URL (`about_page_hero_image`; image).
- Company breadcrumb label (`breadcrumb_parent`; text).

### Introduction

- Introduction - packaging image URL (`about_page_image`; image).
- Image description: The Rock Pack silver packaging pouches on a production conveyor (`about_us_image_description_62`; text).
- Introduction - eyebrow (`about_page_eyebrow`; text).
- Introduction - button text (`about_page_button_text`; text).
- Introduction - button URL (`about_page_button_url`; url).
- **Introduction bullets** (`about_bullets`): Text.
- **Capability tabs** (`about_tabs`): Tab label; Tab content.

### Technical foundation

- Technical foundation - background image URL (`about_page_foundation_image`; image).
- Technical foundation - description (`about_page_foundation_text`; textarea).
- **Technical foundation features** (`foundation_features`): Text; Icon.

### Partnership

- Partnership - description (`about_page_partnership_text`; textarea).
- Partnership - upper image URL (`about_page_partnership_image`; image).
- Image description: Business partners shaking hands (`about_us_image_description_63`; text).
- Partnership - lower image URL (`about_page_collaboration_image`; image).
- Image description: Team members bringing puzzle pieces together (`about_us_image_description_64`; text).
- **Partnership points** (`partnership_points`): Text; Icon.

### Regional growth

- **Regional growth cards** (`growth_cards`): Text; Icon.

### Closing call to action

- Closing - description (`about_page_closing_text`; textarea).
- Closing - button text (`about_page_closing_button_text`; text).
- Closing - button URL (`about_page_closing_button_url`; url).

## History

### Hero

- Page hero image URL (`history_hero_image`; image).
- Page hero eyebrow (`history_hero_eyebrow`; text).

### History timeline

- Page content introduction (`history_page_intro`; textarea).
- **History slides (shared)** (`history`): Title; Description; Milestone image; Milestone icon; Placement.

## Certifications

### Hero

- Certifications page - hero image URL (`certs_page_hero_image`; image).
- Company breadcrumb label (`breadcrumb_parent`; text).

### Introduction and certifications

- Page introduction text (`certs_page_intro`; textarea).
- **Certifications (shared)** (`certs`): Name; Certificate icon.

### Production

- Production - lower image URL (`certs_page_production_image`; image).
- Image description: Packaging material rolls in the production facility (`certifications_image_description_65`; text).
- Production - upper image URL (`certs_page_packaging_image`; image).
- Image description: Printed packaging rolls and aseptic packaging (`certifications_image_description_66`; text).
- Production - first paragraph (`certs_page_production_intro`; textarea).
- Production - second paragraph (`certs_page_production_result`; textarea).

### Quality assurance

- Quality paragraph (`certs_page_quality_text`; textarea).
- **Quality assurance steps** (`quality_steps`): Text.

### Partners

- Partners - first paragraph (`certs_page_partners_intro`; textarea).
- Partners - second paragraph (`certs_page_partners_text`; textarea).
- Partners image URL (`certs_page_partners_image`; image).
- Image description: Understanding our partners and their quality requirements (`certifications_image_description_67`; text).

### Closing statement

- Closing paragraph (`certs_page_commitment_text`; textarea).

## Product Portfolio

### Hero

- Hero image URL (`portfolio_page_hero_image`; image).

### Product portfolio

- Gallery introduction (`portfolio_page_gallery_intro`; textarea).
- **Product gallery (shared)** (`products`): Title / image description; Product image; Product link.

### Quality management

- Quality - first paragraph (`portfolio_page_quality_intro`; textarea).
- Quality - second paragraph (`portfolio_page_quality_text`; textarea).

### Process and materials

- Process and Materials - image URL (`portfolio_page_materials_image`; image).
- Image description: Printed packaging rolls and aseptic packaging materials (`product_portfolio_image_description_70`; text).
- Process and Materials - introduction (`portfolio_page_materials_intro`; textarea).
- **Process and materials steps** (`material_steps`): Title; Description.

### Process Control and Inspection

- Introduction (`process_intro`; textarea).
- **Process cards (shared with Home and Portfolio)** (`process_cards`): Title; Description; Image; Statistic; Statistic label; Card appearance; Image description.

### Localization

- **Localization cards (shared with Home and Portfolio)** (`local_cards`): Title; Description; Image; Card appearance; Image description.

## Planet & Societal Impact

### Hero

- Hero image URL (`planet_page_hero_image`; image).

### Sustainable sourcing

- Sourcing image URL (`planet_page_sourcing_image`; image).
- Image description: Printed packaging rolls and responsibly sourced packaging materials (`planet_image_description_68`; text).
- Sourcing description (`planet_page_sourcing_text`; textarea).

### Vision for Africa

- Vision - first paragraph (`planet_page_vision_text`; textarea).
- Vision - second paragraph (`planet_page_vision_text_2`; textarea).
- Vision image URL (`planet_page_vision_image`; image).
- Image description: A technician inspecting aseptic production equipment (`planet_image_description_69`; text).

### Environmental, Social and Governance

- ESG introduction (`planet_page_esg_intro`; textarea).
- **ESG pillars and points** (`esg_pillars`): Pillar title; Points [Text].

### Closing statement

- Closing statement (`planet_page_statement_text`; textarea).

## Contact Us

### Hero

- Hero image URL (`contact_page_hero_image`; image).

### Contact details

- Address card title (`contact_page_address_title`; text).
- Address (`contact_page_address`; textarea).
- Phone card title (`contact_page_phone_title`; text).
- Phone card description (`contact_page_phone_hint`; text).
- Phone number (`contact_page_phone`; text).
- Email card title (`contact_page_email_title`; text).
- Public email address (`contact_page_email`; email).
- Second public email address (`contact_page_email_2`; email).

### Contact form

- Form introduction label (`contact_page_form_eyebrow`; text).
- Form introduction description (`contact_page_form_text`; textarea).
- Contact Form 7 form (`contact_page_form_id`; select).

Manage field labels, required/optional fields, button text, validation messages and recipient under **Contact > Contact Forms**. The former seven theme controls are retired; their stored values remain and were used when creating the initial CF7 form. The page editor links to the CF7 forms screen.

### Map

- Map image URL (PDF reference shows Los Angeles, not the Cairo address) (`contact_page_map_image`; image).
- Map image description (`contact_page_map_alt`; text).
## Legacy settings

Settings > Rockpack exposes only the remaining scalar controls. Repeated content is managed under Pages > Edit; shared navigation and footer controls are under Appearance > Header & Footer. Contact form fields and mail settings are managed through Contact Form 7's own access controls.

## News articles and job opportunities

- **Posts > Add New:** article title, body, excerpt, author and featured image. Published public posts appear in News and the Home news cards.
- **Careers > Add job opportunity:** role title, description, excerpt and featured image. Opportunity details include Location, Employment type, Applications (Open/Closed), and an optional HTTP(S) application URL or mailto link. Published open roles appear on the Careers page and Home. Without a custom application link, Contact Us opens with the role identified in the message.
- News and Careers listing pages have native pagination and branded detail templates. The local installation includes ten clearly marked sample news posts; no job records have been seeded. Empty states appear when no eligible entries are published.
