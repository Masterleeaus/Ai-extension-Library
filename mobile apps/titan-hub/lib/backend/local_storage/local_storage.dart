import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:get_storage/get_storage.dart';

import '../../core/security/secure_key_value_store.dart';
import '../../core/security/session_token_store.dart';
import '../utils/constants.dart';

const String productId = 'product_id';
const String idKey = 'idKey';
const String nameKey = 'nameKey';
const String tokenKey = 'tokenKey';
const String emailKey = 'emailKey';
const String imageKey = 'imageKey';
const String countryCode = 'countryCode';
const String country = 'country';
const String cardType = 'cardType';
const String showAdKey = 'showAdKey';
const String splashImgKey = 'splashImgKey';
const String isLoggedInKey = 'isLoggedInKey';
const String isDataLoadedKey = 'isDataLoadedKey';
const String isOnBoardDoneKey = 'isOnBoardDoneKey';
const String isScheduleEmptyKey = 'isScheduleEmptyKey';
const String language = 'language';
const String smallLanguage = 'smallLanguage';
const String capitalLanguage = 'capitalLanguage';
const String isEmailVerificationKey = 'isEmailVerificationKey';
const String isKycVerificationKey = 'isKycVerificationKey';
const String isPusherAuthenticationKey = 'isPusherAuthenticationKey';
const String pusherInstanceIdKey = 'pusherInstanceIdKey';

class LocalStorages {
  static const String _secureAccessTokenKey = 'titan.session.access_token';
  static final SecureKeyValueStore _secureStorage =
      FlutterSecureKeyValueStore();
  static final SessionTokenStore _sessionTokens =
      SessionTokenStore(_secureStorage);
  static String? _cachedToken;

  static Future<void> initializeSecureSession() async {
    final session = await _sessionTokens.readSession();
    _cachedToken = session?.accessToken ??
        await _secureStorage.read(_secureAccessTokenKey);

    final box = GetStorage();
    final legacyToken = box.read(tokenKey);
    if (_cachedToken == null &&
        legacyToken is String &&
        legacyToken.isNotEmpty) {
      _cachedToken = legacyToken;
      await _secureStorage.write(_secureAccessTokenKey, legacyToken);
    }
    await box.remove(tokenKey);
  }

  static Future<void> saveSession({
    required String accessToken,
    required String refreshToken,
    required DateTime expiresAt,
  }) async {
    await _sessionTokens.rotateSession(
      accessToken: accessToken,
      refreshToken: refreshToken,
      expiresAt: expiresAt,
    );
    _cachedToken = accessToken;
    await GetStorage().remove(tokenKey);
  }

  static Future<void> saveEmailVerification({
    required bool isEmailVerification,
  }) async {
    final box = GetStorage();
    await box.write(isEmailVerificationKey, isEmailVerification);
    debugPrint(isEmailVerification.toString());
  }

  static Future<void> saveKycVerification({
    required bool isKycVerification,
  }) async {
    final box = GetStorage();
    await box.write(isKycVerificationKey, isKycVerification);
    debugPrint(isKycVerification.toString());
  }

  static Future<void> savePusherAuthenticationKey({
    required bool pusherAuthenticationKey,
  }) async {
    final box = GetStorage();
    await box.write(isPusherAuthenticationKey, pusherAuthenticationKey);
  }

  static bool isPusherAuthentication() {
    return GetStorage().read(isPusherAuthenticationKey) ?? false;
  }

  static Future<void> saveSplashImage({required String image}) async {
    await GetStorage().write(splashImgKey, image);
  }

  static String getSplashImage() {
    return GetStorage().read(splashImgKey);
  }

  static Future<void> saveId({required String id}) async {
    await GetStorage().write(idKey, id);
  }

  static Future<void> saveName({required String name}) async {
    await GetStorage().write(nameKey, name);
  }

  static Future<void> savePusherInstanceId({required String key}) async {
    await GetStorage().write(pusherInstanceIdKey, key);
  }

  static String getPusherInstanceId() {
    return GetStorage().read(pusherInstanceIdKey) ?? '';
  }

  static Future<void> saveEmail({required String email}) async {
    await GetStorage().write(emailKey, email);
  }

  static Future<void> saveToken({required String token}) async {
    _cachedToken = token;
    await _secureStorage.write(_secureAccessTokenKey, token);
    await GetStorage().remove(tokenKey);
  }

  static Future<void> saveCountryCode({
    required String countryCodeValue,
  }) async {
    await GetStorage().write(countryCode, countryCodeValue);
  }

  static Future<void> saveCountry({required String countryValue}) async {
    await GetStorage().write(country, countryValue);
  }

  static Future<void> saveCardType({required String cardName}) async {
    await GetStorage().write(cardType, cardName);
  }

  static Future<void> saveImage({required String image}) async {
    await GetStorage().write(imageKey, image);
  }

  static Future<void> isLoginSuccess({required bool isLoggedIn}) async {
    await GetStorage().write(isLoggedInKey, isLoggedIn);
  }

  static Future<void> dataLoaded({required bool isDataLoad}) async {
    await GetStorage().write(isDataLoadedKey, isDataLoad);
  }

  static Future<void> scheduleEmpty({required bool isScheduleEmpty}) async {
    await GetStorage().write(isScheduleEmptyKey, isScheduleEmpty);
  }

  static Future<void> showAdYes({required bool isShowAdYes}) async {
    await GetStorage().write(showAdKey, isShowAdYes);
  }

  static Future<void> saveOnboardDoneOrNot({
    required bool isOnBoardDone,
  }) async {
    await GetStorage().write(isOnBoardDoneKey, isOnBoardDone);
  }

  static Future<void> saveLanguage({
    required String langSmall,
    required String langCap,
    required String languageName,
  }) async {
    languageStateName = languageName;
    Get.updateLocale(Locale(langSmall, langCap));
    final box = GetStorage();
    await box.write(smallLanguage, langSmall);
    await box.write(capitalLanguage, langCap);
    await box.write(language, languageName);
  }

  static List getLanguage() {
    final small = GetStorage().read(smallLanguage) ?? 'en';
    final capital = GetStorage().read(capitalLanguage) ?? 'EN';
    final languages = GetStorage().read(language) ?? 'English';
    return [small, capital, languages];
  }

  static String getProductId() {
    return GetStorage().read(productId) ?? '';
  }

  static Future<void> saveProductId({required String id}) async {
    await GetStorage().write(productId, id);
  }

  static Future<void> changeLanguage() async {
    await GetStorage().remove(language);
  }

  static String? getId() => GetStorage().read(idKey);

  static String? getName() => GetStorage().read(nameKey);

  static String? getEmail() => GetStorage().read(emailKey);

  static String? getToken() => _cachedToken;

  static String? getCountryCode() {
    final value = GetStorage().read(countryCode);
    debugPrint(value == null ? '##Country Code is null###' : '');
    return value ?? '234';
  }

  static String? getCountry() {
    final value = GetStorage().read(country);
    debugPrint(value == null ? '##Country is null###' : '');
    return value ?? '';
  }

  static String? getImage() => GetStorage().read(imageKey);

  static String? getCardType() => GetStorage().read(cardType);

  static bool isLoggedIn() => GetStorage().read(isLoggedInKey) ?? false;

  static bool isEmailVerification() {
    return GetStorage().read(isEmailVerificationKey) ?? false;
  }

  static bool isKycVerification() {
    return GetStorage().read(isKycVerificationKey) ?? false;
  }

  static bool isDataLoaded() => GetStorage().read(isDataLoadedKey) ?? false;

  static bool isScheduleEmpty() {
    return GetStorage().read(isScheduleEmptyKey) ?? false;
  }

  static bool isOnBoardDone() {
    return GetStorage().read(isOnBoardDoneKey) ?? false;
  }

  static bool showAdPermission() => GetStorage().read(showAdKey) ?? true;

  static Future<void> logout() async {
    _cachedToken = null;
    await _sessionTokens.clear();
    await _secureStorage.delete(_secureAccessTokenKey);

    final box = GetStorage();
    await box.remove(idKey);
    await box.remove(nameKey);
    await box.remove(emailKey);
    await box.remove(imageKey);
    await box.remove(tokenKey);
    await box.remove(isLoggedInKey);
    await box.remove(isOnBoardDoneKey);
    await box.remove(isPusherAuthenticationKey);
    await box.remove(pusherInstanceIdKey);
  }
}
