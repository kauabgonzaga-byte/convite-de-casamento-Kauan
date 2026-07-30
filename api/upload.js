import { randomUUID } from 'node:crypto';
import { put } from '@vercel/blob';
import { adminSession } from '../lib/auth.js';
import { assertSameOrigin, errorResponse, HttpError, json } from '../lib/http.js';

const extensions = { 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp', 'image/gif': 'gif' };

export default {
  async fetch(request) {
    try {
      if (request.method !== 'POST') return json({ error: 'Método não permitido.' }, 405);
      assertSameOrigin(request);
      if (!adminSession(request)) throw new HttpError(401, 'Faça login para enviar uma foto.');
      if (!process.env.BLOB_READ_WRITE_TOKEN) throw new Error('Conecte um Blob Store para habilitar fotos.');
      const form = await request.formData();
      const image = form.get('image');
      if (!image || typeof image.arrayBuffer !== 'function' || !extensions[image.type]) {
        throw new HttpError(400, 'Envie uma imagem JPG, PNG, WEBP ou GIF.');
      }
      if (image.size > 4 * 1024 * 1024) throw new HttpError(400, 'A imagem deve ter no máximo 4 MB.');
      const blob = await put(`gifts/${randomUUID()}.${extensions[image.type]}`, image, { access: 'public', contentType: image.type });
      return json({ ok: true, url: blob.url, pathname: blob.pathname });
    } catch (error) {
      return errorResponse(error);
    }
  },
};
