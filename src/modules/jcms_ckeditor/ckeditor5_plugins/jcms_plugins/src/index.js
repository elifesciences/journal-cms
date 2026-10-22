/**
 * @file The build process always expects an index.js file. Anything exported
 * here will be recognized by CKEditor 5 as an available plugin. Multiple
 * plugins can be exported in this one file.
 *
 * I.e. this file's purpose is to make plugin(s) discoverable.
 */
// cSpell:ignore captionedvideo
// cSpell:ignore elifebutton
// cSpell:ignore figsharemebed
// cSpell:ignore googlemapembed
// cSpell:ignore twitterembed

import CaptionedVideo from './captionedvideo/captionedvideo';
import CaptionedVideoToolbar from "./captionedvideo/captionedvideotoolbar";
import ElifeButton from './elifebutton/elifebutton';
import ElifeButtonToolbar from "./elifebutton/elifebuttontoolbar";
import FigshareEmbed from './figshareembed/figshareembed';
import FigshareEmbedToolbar from "./figshareembed/figshareembedtoolbar";
import GoogleMapEmbed from './googlemapembed/googlemapembed';
import GoogleMapEmbedToolbar from "./googlemapembed/googlemapembedtoolbar";
import TwitterEmbed from './twitterembed/twitterembed';
import TwitterEmbedToolbar from "./twitterembed/twitterembedtoolbar";
import MediaEmbed from './mediaembed/mediaembed';
import MediaEmbedToolbar from './mediaembed/mediaembedtoolbar';
import MediaCaption from './mediacaption/mediacaption';

export default {
  CaptionedVideo,
  CaptionedVideoToolbar,
  ElifeButton,
  ElifeButtonToolbar,
  FigshareEmbed,
  FigshareEmbedToolbar,
  GoogleMapEmbed,
  GoogleMapEmbedToolbar,
  TwitterEmbed,
  TwitterEmbedToolbar,
  MediaEmbed,
  MediaEmbedToolbar,
  MediaCaption
};
