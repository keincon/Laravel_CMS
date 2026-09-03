# Media System

## Purpose / 目的

English: Secure media library with MIME validation and queued image variants.

日本語: MIME検証とキューによる画像バリアント生成を持つメディアライブラリ。

## Flow

```
Upload → validate MIME/extension/size → store → media row
                                              |
                                              v
                                   GenerateMediaVariants job
                                              |
                                              v
                                         media_variants
```

## Rules

- Never trust client MIME; use server-side detection
- Support local + S3 disks via Laravel Filesystem
- Variants: thumbnail, small, medium, large (config/cms.php)
