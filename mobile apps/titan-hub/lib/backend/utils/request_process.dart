// ignore_for_file: constant_identifier_names

import 'package:get/get.dart';

import '../services/api_services.dart';

class RequestProcess extends GetxController {
  Future<T?> request<T>({
    required T Function(Map<String, dynamic>) fromJson,
    required String apiEndpoint,
    required RxBool isLoading,
    bool showResult = false,
    bool showSuccessMessage = false,
    bool isBasic = false,
    required Function(T?) onSuccess,
    HttpMethod method = HttpMethod.GET,
    Map<String, dynamic>? body,
    Map<String, String>? queryParams,
    Function(Object)? onError,
    List<String>? fieldList,
    List<String>? pathList,
  }) async {
    Map<String, String>? stringBody =
        body?.map((key, value) => MapEntry(key, value.toString()));
    try {
      isLoading.value = true;
      update();

      T? value;
      if (method == HttpMethod.POST && fieldList != null && pathList != null) {
        value = await ApiServices.multipartApiService<T>(
          fromJson,
          apiEndpoint,
          stringBody!,
          fieldList,
          pathList,
          showSuccessMessage: showSuccessMessage,
          isBasic: isBasic,
        );
      } else {
        value = await ApiServices.apiService<T>(
          fromJson,
          apiEndpoint,
          method: method == HttpMethod.POST ? 'POST' : 'GET',
          body: body,
          showResult: showResult,
          isBasic: isBasic,
          showSuccessMessage: showSuccessMessage,
        );
      }

      onSuccess(value);
      return value;
    } catch (e) {
      log.e('🐞🐞🐞 Error from API service: $e 🐞🐞🐞');
      if (onError != null) onError(e);
    } finally {
      isLoading.value = false;
      update();
    }
    return null;
  }
}

enum HttpMethod { GET, POST }
