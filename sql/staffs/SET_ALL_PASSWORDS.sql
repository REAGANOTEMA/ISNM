-- ============================================================
-- SET ALL STAFF PASSWORDS + CLEAR ALL LOCKS
-- Run via MySQL CLI or phpMyAdmin SQL tab
-- Generated: 2026-07-03 07:11:48
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

UPDATE staff SET login_attempts = 0, locked_until = NULL;

UPDATE staff SET password = '$2y$10$5ZCEt690hGgitPo/i2hN9u47/msQyL/WGUjLqRrV5FxDkIJ1E8Z4G' WHERE LOWER(email) = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$9OCaF6L19fgSaGCxFIg4r.zqRGhOmJ9NH7O/drLcnZxJuc98fLmIm' WHERE LOWER(email) = 'ceo@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$.ISH3kz4OBP2pm9zZdErAOa.gRlJ6jzXywDjl5KKzXBO4rWynaUX6' WHERE LOWER(email) = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$47TAy3ADQyAKVrB7HKPuw.f60KFLoKwRQOpspDDO0ZRzuodEZN9mu' WHERE LOWER(email) = 'principal@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$fX8gBipJ9jktOlqImT.dGethgebkpMQtISb4HPLKBWzP4W6Sfl8eG' WHERE LOWER(email) = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$PqCRfIJ85BuVxyK7ARsJMOKOX2gzssx9wKlgCMZ2c.ma8X1qYlbWi' WHERE LOWER(email) = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$vIoSrwFiG5x2a.Nzo0qWIecJ2AzDij65kmK4.1CNJBeyCqYYI411m' WHERE LOWER(email) = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$xX6uOjGTbVHnq77cTjKBheWwYY33OeWP/hAb/FfaFVFEoeesjEtc6' WHERE LOWER(email) = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$OEffzU3AOkx8wziH.nt4bu2raX/HyGv3IpdDQXMZqVwYj9MotSj.2' WHERE LOWER(email) = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$f5G4glNdiJ5HhWPrwakHD.FtGARFXBN5mDPbisPemvaSMF5VNAjhS' WHERE LOWER(email) = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$mdRzTcfvpjfW3bT3Sw90ZeI25KAVPfaDOZWfaWNqA2UYFcYiurqvK' WHERE LOWER(email) = 'finance@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$LpzZa5tyhQAgS7ek5nPHB.6sACHFSR8Sy1FTGdAQC6uLBil8GX9xW' WHERE LOWER(email) = 'bursar@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$cmGaNRE4umHoIRfZlQ2zHuSUhCx9U3Ir6dgHlC6Vht5WjUSQnUXQ.' WHERE LOWER(email) = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$YJfwOcBemAZeSBPe5aaMhuDQ5xqsCDWCs.ikP5NeXL2AUqfGIajke' WHERE LOWER(email) = 'secretary@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$cKEebG67aspdUj9BiDNrrO8Y76aIBYHbnlInrSKyvM5MpH97bR2qa' WHERE LOWER(email) = 'admissions@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$FaZokKAdFdpBpRwcSM5dSObZG740ZACI8ahL9vE9Uf5SoWWQUZksC' WHERE LOWER(email) = 'library@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$9Rh2T.Jwe9ykgITF0waOk.TmlCjLQ86NT1.2rNhZJe2kNLv.qq1eK' WHERE LOWER(email) = 'matron@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$I1QssfbA4knhiBcpM6NlLOfaH3wclQ2th8xvQZcDP3kgaRLjIheQi' WHERE LOWER(email) = 'warden@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$pjQ6f52wnY9zYohpBewoTuQ5Z46kg5yHg6SCpDfRh1hhLR2jFK5f6' WHERE LOWER(email) = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$n9XgEm1ItVC9.K7RqadF7eQGZmALTFAS5RKWoCEkXXj0zxUhRIoo.' WHERE LOWER(email) = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$SmT1Yk4QWDbwASz0sPLzv.ywxHcPF2ttF6c3deyN.6A/p4ND.kWC6' WHERE LOWER(email) = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$zWybyrTprzkLr0B/0jwUB.iUeQJ.CVLUTZp9Z0dHfE.TEXPfI6Nza' WHERE LOWER(email) = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$g5jTroKwPIL1SEbLYG4wqO1oPr7T6blGHXKj68gzwhY85SHiSnHBC' WHERE LOWER(email) = 'store@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$.YJEVKnLJMlGQcjCOxy1u.ZuahC98gmg04/RJQEV.6LNyN6MYLE7.' WHERE LOWER(email) = 'drivers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$65qSTfGLgwRrgRNiBuyfTOuqfMmpSuS2QANmmfG6VAw0FGR2aRQzG' WHERE LOWER(email) = 'security@igangaschoolofnursingandmidwifery.ac.ug';

SET FOREIGN_KEY_CHECKS = 1;

-- Verification query:
SELECT id, email, LEFT(password, 30) AS hash_prefix, login_attempts, locked_until, status FROM staff ORDER BY id;
