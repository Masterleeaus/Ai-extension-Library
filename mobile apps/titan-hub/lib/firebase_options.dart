// Sanitised donor configuration shape.
// Generate Titan-owned values with FlutterFire outside Git before deployment.
// ignore_for_file: type=lint
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError('Firebase is not configured for web.');
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'Titan-owned Firebase configuration has not been generated for this platform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'TITAN_FIREBASE_ANDROID_API_KEY',
    appId: 'TITAN_FIREBASE_ANDROID_APP_ID',
    messagingSenderId: 'TITAN_FIREBASE_MESSAGING_SENDER_ID',
    projectId: 'TITAN_FIREBASE_PROJECT_ID',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'TITAN_FIREBASE_IOS_API_KEY',
    appId: 'TITAN_FIREBASE_IOS_APP_ID',
    messagingSenderId: 'TITAN_FIREBASE_MESSAGING_SENDER_ID',
    projectId: 'TITAN_FIREBASE_PROJECT_ID',
    iosBundleId: 'io.titanzero.hub',
  );
}
