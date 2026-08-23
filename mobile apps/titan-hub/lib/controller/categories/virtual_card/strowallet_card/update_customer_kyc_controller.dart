import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../../../backend/model/categories/virtual_card/strowallet_models/strowallet_card_create_info.dart';
import '../../../../backend/model/common/common_success_model.dart';
import '../../../../backend/services/api_endpoint.dart';
import '../../../../backend/utils/request_process.dart';
import 'strowallelt_info_controller.dart';

class UpdateCustomerKycController extends GetxController {
  final firstNameController = TextEditingController();
  final lastNameController = TextEditingController();
  final controller = Get.put(VirtualStrowalletCardController());
  RxString idImage = ''.obs;
  RxString userPhoto = ''.obs;

  @override
  void onInit() {
    getStrowalletCardCreateInfo();
    super.onInit();
  }

  final _isCreateCardInfoLoading = false.obs;
  bool get isCreateCardInfoLoading => _isCreateCardInfoLoading.value;

  late StrowalletCardCreateInfo _strowalletCardCreateInfo;
  StrowalletCardCreateInfo get strowalletCardCreateInfo =>
      _strowalletCardCreateInfo;

  Future<StrowalletCardCreateInfo?> getStrowalletCardCreateInfo() async {
    controller.listImagePath.clear();
    controller.listFieldName.clear();
    return RequestProcess().request<StrowalletCardCreateInfo>(
      fromJson: StrowalletCardCreateInfo.fromJson,
      apiEndpoint: ApiEndpoint.strowalletCardInfo,
      isLoading: _isCreateCardInfoLoading,
      onSuccess: (value) {
        _strowalletCardCreateInfo = value!;
        final data = _strowalletCardCreateInfo.data.customerExist;
        firstNameController.text = data.firstName;
        lastNameController.text = data.lastName;
        idImage.value = data.idImage;
        userPhoto.value = data.userPhoto;
      },
    );
  }

  // update customer kyc

  final _isConfirmLoading = false.obs;
  bool get isConfirmLoading => _isConfirmLoading.value;

  late CommonSuccessModel _commonSuccessModel;
  CommonSuccessModel get commonSuccessModel => _commonSuccessModel;

  Future<CommonSuccessModel?> updateCustomerKyc() async {
    Map<String, dynamic> inputBody = {
      'first_name': firstNameController.text,
      'last_name': lastNameController.text,
    };

    return RequestProcess().request<CommonSuccessModel>(
      fromJson: CommonSuccessModel.fromJson,
      apiEndpoint: ApiEndpoint.strowalletUpdateCustomerURl,
      isLoading: _isConfirmLoading,
      method: HttpMethod.POST,
      body: inputBody,
      fieldList: controller.listFieldName,
      pathList: controller.listImagePath,
      showSuccessMessage: true,
      onSuccess: (value) {
        _commonSuccessModel = value!;
      },
    );
  }
}
