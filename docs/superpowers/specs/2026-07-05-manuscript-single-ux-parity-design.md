# Manuscript Single UX Parity Design

## Objective

Make `single-manuscrito` inherit the same editorial shell used by `single-post`, limited to the global page and header experience.

## Scope

This change covers only the shared editorial chrome around the manuscript page:

- beige page background
- dark editorial header
- white navigation and social links inside the header
- search field styling inside the header banner

This change does not alter:

- manuscript viewer behavior
- manuscript search logic
- manuscript content layout
- login/account form affordances such as the password toggle label

## Files In Scope

- `resources/assets/styles/common/_global.scss`
- `resources/assets/styles/layouts/_header.scss`
- `resources/assets/styles/components/_navigation.scss`
- `resources/assets/styles/components/_forms.scss`

## Design

`single-manuscrito` should be treated as an editorial page variant anywhere the theme already branches for `single-post`, `blog`, or archive contexts. The manuscript page should therefore receive the same background and header contrast rules instead of introducing a separate visual system.

The implementation should extend existing selector groups by adding `single-manuscrito` where needed, rather than creating duplicate standalone blocks. This keeps the behavior aligned with the post templates and reduces the risk of the two page types drifting apart later.

## Verification

The implementation is complete when:

- the manuscript single uses the beige background like `single-post`
- the header/banner switches to the dark editorial version on manuscript singles
- navigation, social links, and header search remain legible in that dark header
- unrelated form tweaks remain outside this change set
